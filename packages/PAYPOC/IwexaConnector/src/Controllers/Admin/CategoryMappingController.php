<?php

namespace Webkul\PAYPOC\IwexaConnector\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\PAYPOC\IwexaConnector\Models\CategoryMapping;
use Webkul\PAYPOC\IwexaConnector\Services\CategoryMappingService;

class CategoryMappingController
{
    protected $categoryMappingService;

    public function __construct(CategoryMappingService $categoryMappingService)
    {
        $this->categoryMappingService = $categoryMappingService;
    }

    public function index(): View
    {
        $mappings = CategoryMapping::paginate(20);
        return view('iwexa::admin.category-mappings.index', ['mappings' => $mappings]);
    }

    public function create(): View
    {
        return view('iwexa::admin.category-mappings.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_category' => 'required|string',
            'paypoc_category_id' => 'nullable|integer',
            'bagisto_category_id' => 'nullable|integer',
            'product_type' => 'nullable|string',
            'vendor_code' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $this->categoryMappingService->createMapping($data);

        return redirect()->route('admin.iwexa.category-mappings.index')
            ->with('success', 'Category mapping created successfully');
    }

    public function edit(int $id): View
    {
        $mapping = CategoryMapping::findOrFail($id);
        return view('iwexa::admin.category-mappings.edit', ['mapping' => $mapping]);
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $mapping = CategoryMapping::findOrFail($id);
        
        $data = $request->validate([
            'source_category' => 'required|string',
            'paypoc_category_id' => 'nullable|integer',
            'bagisto_category_id' => 'nullable|integer',
            'product_type' => 'nullable|string',
            'vendor_code' => 'nullable|string',
            'override' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $this->categoryMappingService->updateMapping($mapping, $data);

        return redirect()->route('admin.iwexa.category-mappings.index')
            ->with('success', 'Category mapping updated successfully');
    }

    public function destroy(int $id): RedirectResponse
    {
        $mapping = CategoryMapping::findOrFail($id);
        $mapping->delete();

        return back()->with('success', 'Category mapping deleted successfully');
    }
}
