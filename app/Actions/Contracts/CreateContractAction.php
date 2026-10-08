<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Contract;
use Illuminate\Support\Facades\DB;

class CreateContractAction
{
    /**
     * Create a new commercial contract along with its contractual line items atomically.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Contract
    {
        return DB::transaction(function () use ($data): Contract {
            $contractData = collect($data)->except('items')->toArray();
            $contract = Contract::create($contractData);

            $items = (array) ($data['items'] ?? []);
            foreach ($items as $item) {
                if (blank($item['designation'] ?? null)) {
                    continue;
                }
                $contract->items()->create($item);
            }

            return $contract;
        });
    }
}
