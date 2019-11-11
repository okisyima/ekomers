<?php

namespace App\Http\Controllers;

use File;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\ProductJob;
use App\Product; //load model product
use App\Category; //loadl model category

class ProductController extends Controller
{
    public function index()
    {
        //BUAT QUERY MENGGUNAKAN MODEL PRODUCT, DENGAN MENGURUTKAN DATA BERDASARKAN CREATED_AT
        //KEMUDIAN LOAD TABLE YANG BERELASI MENGGUNAKAN EAGER LOADING WITH()
        //ADAPUN CATEGORY ADALAH NAMA FUNGSI YANG NNTINYA AKAN DITAMBAHKAN PADA PRODUCT MODEL
        $product = Product::with(['category'])->orderBy('created_at', 'DESC');

        //JIKA TERDAPAT PARAMETER PENCARIAN DI URL ATAU CARI PADA URL TIDAK SAMA DENGAN KOSONG
        if (request()->cari != '') {
            //MAKA LAKUKAN FILTERING DATA BERDASARKAN NAME DAN VALUENYA SESUAI DENGAN PENCARIAN YANG DILAKUKAN USER
            $product = $product->where('name', 'LIKE', '%' . request()->cari . '%');
        }

        //TERAKHIR LOAD 10 DATA PER HALAMANNYA
        $product = $product->paginate(10);

        //LOAD VIEW INDEX.BLADE.PHP YANG BERADA DIDALAM FOLDER PRODUCTS
        //DAN PASSING VARIABLE $PRODUCT KE VIEW AGAR DAPAT DIGUNAKAN
        return view('products.index', compact('product'));
    }

    public function create()
    {
        //QUERY UNTUK MENGAMBIL SEMUA DATA CATEGORY
        $category = Category::orderBy('name', 'DESC')->get();

        //LOAD VIEW create.blade.php` YANG BERADA DIDALAM FOLDER PRODUCTS
        //DAN PASSING DATA CATEGORY
        return view('products.create', compact('category'));
    }

    public function store(Request $request)
    {
        // dd($request->all());

        // VALIDASI REQUESTNYA
        $this->validate($request, [
            'name'          => 'required|string|max:100',
            'description'   => 'required',
            'category_id'   => 'required|exists:categories,id', //CATEGORY_ID KITA CEK HARUS ADA DI TABLE CATEGORIES DENGAN FIELD ID
            'price'         => 'required|integer',
            'weight'        => 'required|integer',
            'image'         => 'required|image|mimes:png,jpeg,jpg' //GAMBAR DIVALIDASI HARUS BERTIPE PNG,JPG DAN JPEG
        ]);

        // JIKA FILENYA ADA
        if ($request->hasFile('image')) {
            //MAKA KITA SIMPAN SEMENTARA FILE TERSEBUT KEDALAM VARIABLE FILE
            $file = $request->file('image');
            //KEMUDIAN NAMA FILENYA KITA BUAT CUSTOMER DENGAN PERPADUAN TIME DAN SLUG DARI NAMA PRODUK. ADAPUN EXTENSIONNYA KITA GUNAKAN BAWAAN FILE TERSEBUT
            $fileName = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            //SIMPAN FILENYA KEDALAM FOLDER PUBLIC/PRODUCTS, DAN PARAMETER KEDUA ADALAH NAMA CUSTOM UNTUK FILE TERSEBUT
            $file->storeAs('public/products', $fileName);
            //SETELAH FILE TERSEBUT DISIMPAN, KITA SIMPAN INFORMASI PRODUKNYA KEDALAM DATABASE
            $product = Product::create([
                'name'          => $request->name,
                'slug'          => $request->name,
                'category_id'   => $request->category_id,
                'description'   => $request->description,
                'image'         => $fileName, //PASTIKAN MENGGUNAKAN VARIABLE FILENAM YANG HANYA BERISI NAMA FILE SAJA (STRING)
                'price'         => $request->price,
                'weight'        => $request->weight,
            ]);

            //JIKA SUDAH MAKA REDIRECT KE LIST PRODUK
            return redirect(route('product.index'))->with(['success' => 'Produk Baru Ditambahkan!']);
        }
    }

    public function edit($id)
    {
        $product    = Product::find($id); //AMBIL DATA PRODUK TERKAIT BERDASARKAN ID
        $category   = Category::orderBy('name', 'DESC')->get(); //AMBIL SEMUA DATA KATEGORI

        return view('products.edit', compact('product', 'category')); //LOAD VIEW DAN PASSING DATANYA KE VIEW
    }

    public function update(Request $request, $id)
    {
        // VALIDASI DATA YANG DIKIRIM
        $this->validate($request, [
            'name'          => 'required|string|max:100',
            'description'   => 'required',
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|integer',
            'weight'        => 'required|integer',
            'image'         => 'nullable|image|mimes:png,jpg,jpeg'
        ]);

        $product    = Product::find($id); //AMBIL DATA PRODUK YANG AKAN DIEDIT BERDASARKAN ID
        $fileName   = $product->image; //SIMPAN SEMENTARA NAMA FILE IMAGE SAAT INI

        //JIKA ADA FILE GAMBAR YANG DIKIRIM
        if ($request->hasFile('image')) {
            $file       = $request->file('image');
            $fileName   = time() . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            //MAKA UPLOAD FILE TERSEBUT
            $file->storeAs('public/products', $fileName);
            //DAN HAPUS FILE GAMBAR YANG LAMA
            File::delete(storage_path('app/public/products/' . $product->image));
        }

        //KEMUDIAN UPDATE PRODUK TERSEBUT
        $product->update([
            'name'          => $request->name,
            'description'   => $request->description,
            'category_id'   => $request->category_id,
            'price'         => $request->price,
            'weight'        => $request->weight,
            'image'         => $fileName
        ]);

        return redirect(route('product.index'))->with(['success' => 'Data Product Diperbaharui']);
    }

    public function destroy($id)
    {
        $product = Product::find($id); //QUERY UNTUK MENGAMBIL DATA PRODUK BERDASARKAN ID
        //HAPUS FILE IMAGE DARI STORAGE PATH DIIKUTI DENGNA NAMA IMAGE YANG DIAMBIL DARI DATABASE
        File::delete(storage_path('app/public/products/' . $product->image));
        //KEMUDIAN HAPUS DATA PRODUK DARI DATABASE
        $product->delete();
        //DAN REDIRECT KE HALAMAN LIST PRODUK
        return redirect(route('product.index'))->with(['success' => 'Produk Sudah Dihapus']);
    }

    public function massUploadForm()
    {
        $category = Category::orderBy('name', 'DESC')->get();
        return view('products.bulk', compact('category'));
    }

    public function massUpload(Request $request)
    {
        // validasi data yang dikirim
        $this->validate($request, [
            'category_id'   => 'required|exists:categories,id',
            'file'          => 'required|mimes:xlsx'
        ]);

        // jika file nya ada
        if ($request->hasFile('file')) {
            $file       = $request->file('file');
            $fileName   = time() . '-product.' . $file->getClientOriginalExtension();
            $file->storeAs('public/uploads', $fileName); //maka simpan file tersebut di storeage/app/public/uploads

            //BUAT JADWAL UNTUK PROSES FILE TERSEBUT DENGAN MENGGUNAKAN JOB
            //ADAPUN PADA DISPATCH KITA MENGIRIMKAN DUA PARAMETER SEBAGAI INFORMASI
            //YAKNI KATEGORI ID DAN NAMA FILENYA YANG SUDAH DISIMPAN
            ProductJob::dispatch($request->category_id, $fileName);
            return redirect()->back()->with(['success' => 'Upload Produk Dijadwalkan']);
        }
    }
}
