<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateBusinessAction;
use App\Actions\DeleteBusinessAction;
use App\Actions\UpdateBusinessAction;
use App\Data\BusinessData;
use App\Models\Business;
use App\Services\TenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class BusinessController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('business/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessData $data, CreateBusinessAction $action): RedirectResponse
    {
        $business = $action->handle(Auth::user(), $data);

        // Automatically set the newly created business as the current business context
        TenantResolver::setCurrentBusiness($business);

        return to_route('dashboard')
            ->with('success', 'Business created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Business $business): Response
    {
        $this->authorize('update', $business);

        return Inertia::render('business/edit', [
            'business' => BusinessData::fromModel($business),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BusinessData $data, Business $business, UpdateBusinessAction $action): RedirectResponse
    {
        $this->authorize('update', $business);

        $action->handle($business, $data);

        return to_route('business.edit', $business)
            ->with('success', 'Business updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Business $business, DeleteBusinessAction $action): RedirectResponse
    {
        $this->authorize('delete', $business);

        $action->handle($business);

        return to_route('dashboard')
            ->with('success', 'Business deleted successfully');
    }
}
