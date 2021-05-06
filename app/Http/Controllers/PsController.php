<?php

namespace App\Http\Controllers;

use App\Models\Ps;

use Illuminate\Support\Facades\Validator;
use Exception;

class PsController extends ApiController
{

    private $rules;
    private $customMessages = [
        'required' => ':attribute est obligatoire.',
        'unique' => ':attribute existe déjà.'
    ];

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->rules = array_merge($this->psRules(), $this->exProRules(), $this->expertiseRules(), $this->situationRules());
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        $psList = Ps::all();
        return $this->successResponse($this->psTransformer->transformCollection($psList->all()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return mixed
     */
    public function store()
    {
        $id = request()->nationalId;
        if ($this->isNewPs($id)) {
            Ps::create($this->validatePs());
        }
        return $this->successResponse(null, 'Creation du Ps avec succès');
    }

    /**
     * Store or Update the specified resource in storage.
     *
     * @return mixed
     */
    public function storeOrUpdate()
    {
        $psId = request()->nationalId;
        $validatedPs = $this->validatePs();
        $ps = Ps::find(urldecode($psId));

        if(!$ps) {
            try {
                Ps::create($validatedPs);
                return $this->successResponse(null, 'Creation du Ps avec succès');
            } catch (Exception $e) { // in case of concurrent create in DB
                $ps = Ps::find(urldecode($psId));
            }
        }

        $psData = $this->getNested($validatedPs, 'professions');
        $ps->update($psData['itself']);

        foreach ($psData['professions'] as $professionData) {
            $professionData['exProId'] = ($professionData['code'] ?? '').($professionData['categoryCode'] ?? '');

            $profession = $ps->professions()->firstWhere('exProId', $professionData['exProId']);
            if (!$profession) {
                $profession = $ps->professions()->create($professionData);
            } else {
                $profession->update($professionData);
            }

            $expertises = $this->getNested($professionData, 'expertises')['expertises'];
            foreach ($expertises as $expertiseData) {
                $expertiseData['expertiseId'] = ($expertiseData['code'] ?? '').($expertiseData['categoryCode'] ?? '');

                $expertise = $profession->expertises()->firstWhere('expertiseId', $expertiseData['expertiseId']);
                if (!$expertise) {
                    $profession->expertises()->create($expertiseData);
                } else {
                    $expertise->update($expertiseData);
                }
            }

            $situations = $this->getNested($professionData, 'workSituations')['workSituations'];
            foreach ($situations as $situationData) {
                $situationData['situId'] = ($situationData['roleCode'] ?? '').($situationData['modeCode'] ?? '');

                $situation = $profession->workSituations()->firstWhere('situId', $situationData['situId']);
                if (!$situation) {
                    $profession->workSituations()->create($situationData);
                } else {
                    $situation->update($situationData);
                }
            }
        }

        return $this->successResponse(null, 'Mise à jour du Ps avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param $psId
     * @return mixed
     */
    public function show($psId)
    {
        $psId = $this->getPsOrFail($psId);
        return $this->successResponse($this->psTransformer->transform($psId));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param $psId
     * @return mixed
     */
    public function update($psId)
    {
        $psId = $this->getPsOrFail($psId);
        $psId->update(array_filter(request()->all()));
        return $this->successResponse(null, 'Mise à jour du Ps avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $psId
     * @return mixed
     * @throws Exception
     */
    public function destroy($psId)
    {
        $psId = $this->getPsOrFail($psId);
        $psId->delete();
        return $this->successResponse(null, 'Supression du Ps avec succès.');
    }

    private function psRules(): array
    {
        return [
            'idType' => 'nullable|string',
            'id' => 'nullable|string',
            'nationalId' => 'required', //'required|unique:ps',
            'lastName' => 'nullable|string',
            'firstName' => 'nullable|string',
            'dateOfBirth' => 'nullable|string',
            'birthAddressCode' => 'nullable|string',
            'birthCountryCode' => 'nullable|string',
            'birthAddress' => 'nullable|string',
            'genderCode' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'salutationCode' => 'nullable|string',
            'professions' => 'nullable|array'
        ];
    }

    private function exProRules(): array
    {
        return [
            'professions.*.code' => 'nullable|string',
            'professions.*.categoryCode' => 'nullable|string',
            'professions.*.salutationCode' => 'nullable|string',
            'professions.*.lastName' => 'nullable|string',
            'professions.*.firstName' => 'nullable|string',
            'professions.*.expertises' => 'nullable|array',
            'professions.*.workSituations' => 'nullable|array',
        ];
    }

    private function expertiseRules(): array
    {
        return [
            'professions.*.expertises.*.code' => 'nullable|string',
            'professions.*.expertises.*.categoryCode' => 'nullable|string',
        ];
    }

    private function situationRules(): array
    {
        return [
            'professions.*.workSituations.*.modeCode' => 'nullable|string',
            'professions.*.workSituations.*.activitySectorCode' => 'nullable|string',
            'professions.*.workSituations.*.pharmacistTableSectionCode' => 'nullable|string',
            'professions.*.workSituations.*.roleCode' => 'nullable|string'
        ];
    }

    private function validatePs()
    {
        $validator = Validator::make(request()->all(), $this->rules, $this->customMessages);

        if ($validator->fails()) {
            $this->errorResponse($validator->errors()->first(), 500)->send();
            die();
        }

        $ps = $validator->validate();

        foreach ($ps['professions'] as &$profession) {
            $profession['exProId'] = ($profession['code'] ?? '').($profession['categoryCode'] ?? '');
            foreach ($profession['expertises'] as &$expertise) {
                $expertise['expertiseId'] = ($expertise['code'] ?? '').($expertise['categoryCode'] ?? '');
            }
            foreach ($profession['workSituations'] as &$situation) {
                $situation['situId'] = ($situation['roleCode'] ?? '').($situation['modeCode'] ?? '');
            }
        }
        return $ps;
    }

}
