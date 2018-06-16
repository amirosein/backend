<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 19/1/18
 * Time: 2:50 PM
 */

namespace App\Repository\Services;


use App\Codec;
use App\Exceptions\GeneralException;
use App\Project;
use App\Thing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CodecService
{

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateCreateCodec(Request $request)
    {
        $messages = [
            'name.required' => 'لطفا نام کدک را وارد کنید',
            'code.required' => 'لطفا کدک را وارد کنید',

        ];

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'name' => 'required', 'string', 'max:255'
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param Request $request
     * @param Project $project
     * @return void
     */
    public function insertCodec(Request $request, Project $project = null)
    {
        $user = Auth::user();
        $data = [
            'name' => $request->get('name'),
            'code' => $request->get('code'),
        ];
        if ($project) {
            $data['user_id'] = $user['_id'];
            $data['project_id'] = $project['_id'];
        } else
            $data['global'] = true;

        $codec = Codec::create($data);
        $codec->save();
        return $codec;
    }

    /**
     * @param Request $request
     * @param Codec $codec
     * @return Codec
     */
    public function updateCodec(Request $request, Codec $codec)
    {
        $codec->name = $request->get('name');
        $codec->code = $request->get('code');
        $codec->save();
        return $codec;
    }
}