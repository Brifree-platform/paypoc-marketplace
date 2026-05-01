<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Webkul\PAYPOC\IwexaConnector\Models\ProductTypeMapping;
use Webkul\PAYPOC\IwexaConnector\Services\ProductTypeMappingService;

class ProductTypeMappingController
{
    protected $productTypeMappingService;

    public function __construct(ProductTypeMappingService $productTypeMappingService)
    {
        $this->productTypeMappingService = $productTypeMappingService;
    }

    public function index(): View
    {
        $mappings = ProductTypeMapping::paginate(20);
        return view('iwexa::admin.product-type-mappings.index', ['mappings' => $mappings]);
    }

    public function show(int $id): View
    {
        $mapping = ProductTypeMapping::findOrFail($id);
        $attributeMappings = $mapping->attributeMappings;
        return view('iwexa::admin.product-type-mappings.show', [
            'mapping' => $mapping,
            'attributeMappings' => $attributeMappings,
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $mapping = ProductTypeMapping::findOrFail($id);
        $this->productTypeMappingService->approveMapping($mapping);
        
        return back()->with('success', 'Product type mapping approved successfully');
    }

    public function reject(int $id): RedirectResponse
    {
        $mapping = ProductTypeMapping::findOrFail($id);
        $mapping->update(['status' => 'inactive']);
        
        return back()->with('success', 'Product type mapping rejected');
    }
}
