<?php

namespace Bookkeeper\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FinancialRecord extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'isExpense' => $this->isExpense,
            'due_date' => $this->due_date,
            'value' => $this->value,
            'currency' => $this->currency,
            'recursive' => $this->recursive,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
