<?php

namespace Bookkeeper\Controllers;

use Bookkeeper\Models\FinancialRecord;
use Bookkeeper\Resources\FinancialRecordCollection;
use Bookkeeper\Resources\FinancialRecord as FinancialRecordResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FinancialRecordController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return FinancialRecordCollection
     */
    public function index()
    {
        return new FinancialRecordCollection(FinancialRecord::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return FinancialRecordResource
     */
    public function store(Request $request)
    {
        $frData = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'due_date' => 'required|date_format:Y-m-d H:i:s',
            'value' => 'required|numeric|between:0,99999.99',
            'recursive' => 'required|boolean',
            'started_at' => 'required_if:recursive,true|date_format:Y-m-d H:i:s',
            'ended_at' => 'required_if:recursive,true|nullable|date_format:Y-m-d H:i:s',
            'isExpense' => 'required|boolean',
            'payment_type' => 'nullable|string'
        ]);

        $financialRecord = DB::transaction(function () use ($frData) {
            $user = auth()->user();
            Arr::set($frData, 'user_id', $user->id);

            $financialRecord = FinancialRecord::create($frData);

            $asData = Arr::only(
                $frData,
                ['name','description','due_date','value','currency','isExpense','payment_type']
            );
            Arr::set($asData, 'user_id', $user->id);
            Arr::set($asData, 'financial_record_id', $financialRecord->id);

            $financialRecord->accountStatement()->create($asData);

            return $financialRecord;
        });

        return new FinancialRecordResource($financialRecord->fresh());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return FinancialRecordResource
     */
    public function show($id)
    {
        return new FinancialRecordResource(FinancialRecord::find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return FinancialRecordResource
     */
    public function update(Request $request, $id)
    {
        $financialRecord = FinancialRecord::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'due_date' => 'required|date_format:Y-m-d H:i:s',
            'value' => 'required|numeric|between:0,99999.99',
            'recursive' => 'required|boolean',
            'started_at' => 'required_if:recursive,true|date_format:Y-m-d H:i:s',
            'ended_at' => 'required_if:recursive,true|nullable|date_format:Y-m-d H:i:s',
            'isExpense' => 'required|boolean',
            'payment_type' => 'nullable|string'
        ]);

        $financialRecord->update($validatedData);


        return new FinancialRecordResource($financialRecord->fresh());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return FinancialRecordResource
     */
    public function destroy($id)
    {
        FinancialRecord::findOrFail($id);

        FinancialRecord::destroy($id);

        return new FinancialRecordResource(new FinancialRecord());
    }
}
