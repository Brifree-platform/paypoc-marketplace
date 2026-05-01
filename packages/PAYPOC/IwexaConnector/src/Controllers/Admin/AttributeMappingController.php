<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\PAYPOC\IwexaConnector\Models\AttributeMapping;
use Webkul\PAYPOC\IwexaConnector\Services\AttributeMappingService;

class AttributeMappingController
{
    protected $attributeMappingService;

    public function __construct(AttributeMappingService $attributeMappingService)
    {
        $this->attributeMappingService = $attributeMappingService;
    }

    public function index(): View
    {
        $mappings = AttributeMapping::paginate(20);
        return view('iwexa::admin.attribute-mappings.index', ['mappings' => $mappings]);
    }

    public function show(int $id): View
    {
        $mapping = AttributeMapping::findOrFail($id);
        $valueMapping = $mapping->attributeValueMappings;
        return view('iwexa::admin.attribute-mappings.show', [
            'mapping' => $mapping,
            'valueMapping' => $valueMapping,
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $mapping = AttributeMapping::findOrFail($id);
        $this->attributeMappingService->approveMapping($mapping);

        return back()->with('success', 'Attribute mapping approved successfully');
    }

    public function configure(int $id, Request $request): RedirectResponse
    {
        $mapping = AttributeMapping::findOrFail($id);

        $data = $request->validate([
            'bagisto_attribute_code' => 'nullable|string',
            'bagisto_attribute_id' => 'nullable|integer',
            'required' => 'boolean',
            'variant_axis' => 'boolean',
            'searchable' => 'boolean',
            'filterable' => 'boolean',
        ]);

        $this->attributeMappingService->updateMapping($mapping, $data);

        return back()->with('success', 'Attribute mapping configured successfully');
    }
}
