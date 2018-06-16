<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Package;
use App\Repository\Helper\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{


    /**
     * PackageController constructor.
     */
    public function __construct()
    {

        $this->middleware('can:view,package')->only(['get']);
        $this->middleware('can:update,package')->only(['activate', 'update']);
        $this->middleware('can:delete,package')->only(['delete']);
        $this->middleware('can:create,App\Package')->only(['create', 'all']);
    }


    /**
     * @param Request $request
     * @return array
     */
    public function list(Request $request)
    {
        $packages = Package::where('is_active', true)->get();
        return Response::body(['packages' => $packages]);
    }

    /**
     * @param Package $package
     * @return array
     */
    public function get(Package $package)
    {
        return Response::body(compact('package'));
    }


    /**
     * @param Request $request
     * @return array
     */
    public function all(Request $request)
    {
        $packages = Package::get();
        return Response::body(['packages' => $packages]);
    }

    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:155',
            'time' => 'required|integer',
            'project_num' => 'required|integer',
            'node_num' => 'required|integer',
            'price' => 'required|integer',
        ], [
            'name.required' => 'لطفا نام بسته را وارد کنید',
            'time.required' => 'لطفا زمان بسته را وارد کنید', 'time.integer' => 'لطفا زمان بسته را درست وارد کنید',
            'project_num.required' => 'لطفا تعداد پروژه‌ها را وارد کنید', 'time.integer' => 'لطفا تعداد پروژه‌ها را درست وارد کنید',
            'node_num.required' => 'لطفا تعداد نودها را وارد کنید', 'node_num.integer' => 'لطفا تعداد نودها را درست وارد کنید',
            'price.required' => 'لطفا قیمت را وارد کنید', 'price.integer' => 'لطفا قیمت را درست وارد کنید',
        ]);
        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);

        $data = $request->only(['name', 'time', 'project_num', 'node_num', 'price']);
        $data['is_active'] = true;
        return Response::body(['package' => Package::create($data)]);

    }


    /**
     * @param Package $package
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function update(Package $package, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:155',
            'time' => 'required|integer',
            'project_num' => 'required|integer',
            'node_num' => 'required|integer',
            'price' => 'required|integer',
        ], [
            'name.required' => 'لطفا نام بسته را وارد کنید',
            'time.required' => 'لطفا زمان بسته را وارد کنید', 'time.integer' => 'لطفا زمان بسته را درست وارد کنید',
            'project_num.required' => 'لطفا تعداد پروژه‌ها را وارد کنید', 'time.integer' => 'لطفا تعداد پروژه‌ها را درست وارد کنید',
            'node_num.required' => 'لطفا تعداد نودها را وارد کنید', 'node_num.integer' => 'لطفا تعداد نودها را درست وارد کنید',
            'price.required' => 'لطفا قیمت را وارد کنید', 'price.integer' => 'لطفا قیمت را درست وارد کنید',
        ]);
        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);

        $data = $request->only(['name', 'time', 'project_num', 'node_num', 'price']);
        $package->update($data);
        return Response::body(['package' => $package]);

    }

    /**
     * @param Package $package
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function activate(Package $package, Request $request)
    {
        if ($package['default'])
            throw new GeneralException('بسته پیش فرض را نمیتوانید برای خرید فعال کنید', GeneralException::ACCESS_DENIED);

        if ($request->get('active'))
            $package->is_active = true;
        else
            $package->is_active = false;
        $package->save();
        return Response::body(compact('package'));
    }

    public function delete(Package $package)
    {
        if ($package['default'])
            throw new GeneralException('بسته پیش فرض را نمیتوانید حذف کنید', GeneralException::ACCESS_DENIED);
        $package->delete();
        return Response::body(['success' => true]);
    }


}
