<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/15/17
 * Time: 3:34 PM
 */

namespace App\Repository\Services;


use App\Codec;
use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Project;
use App\Thing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use MongoDB\BSON\ObjectId;

class ProjectService
{
    protected $coreService;
    protected $loraService;


    public function __construct(CoreService $coreService, LoraService $loraService)
    {
        $this->coreService = $coreService;
        $this->loraService = $loraService;
    }

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateCreateProject(Request $request)
    {
        $messages = [
            'name.required' => 'لطفا نام پروژه را وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     * @throws LoraException
     */
    public function insertProject(Request $request)
    {
        $id = new ObjectId();
        $application_id = $this->loraService->postApp($request->get('description'), $id);
        $container = $this->coreService->postProject($id);
        $project = Project::create([
            '_id' => $id,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'active' => true,
            'container' => $container,
            'application_id' => $application_id
        ]);
        return $project;
    }

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateUpdateProject(Request $request)
    {
        $messages = [
            'name.filled' => 'لطفا نام پروژه را وارد کنید',
            'name.unique' => 'این پرژوه قبلا وجود دارد',
            'description.filled' => 'لطفا توضیحات را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'filled|string|max:255',
            'description' => 'filled|string',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param Request $request
     * @param Project $project
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function updateProject(Request $request, Project $project)
    {
        if ($request->get('name'))
            $project->name = $request->get('name');
        if ($request->get('description'))
            $project->description = $request->get('description');
        $project->save();

        return $project;
    }


    /**
     * @param Project $project
     * @param Thing $thing
     * @param $codec
     * @throws \App\Exceptions\GeneralException
     */
    public function addThing(Project $project, Thing $thing, $codec)
    {
        $this->coreService->postThing($project, $thing);
        if ($codec)
            $this->coreService->sendCodec($project, $thing, $codec->code);
    }

    /**
     * @param Project $project
     * @param array $aliases
     * @throws GeneralException
     */
    public function setAliases(Project $project, $aliases)
    {
        if (!$aliases || !$this->validateAlias($aliases))
            throw new GeneralException('لطفا اطلاعات را درست وارد کنید.', GeneralException::VALIDATION_ERROR);
        $project['aliases'] = $aliases;
        $project->save();
    }


    private function validateAlias($aliases)
    {
        foreach ($aliases as $a)
            if (gettype($a) !== 'string' && gettype($a) !== 'integer')
                return false;
        return true;
    }
}