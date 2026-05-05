<?php

namespace Webkul\PAYPOC\IwexaConnector\Services;

use Webkul\PAYPOC\IwexaConnector\Repositories\IwexaVendorRepository;
use Webkul\PAYPOC\IwexaConnector\Models\IwexaVendor;
use Illuminate\Support\Facades\Validator;
use Exception;

class VendorImportService
{
    protected $vendorRepository;

    public function __construct(IwexaVendorRepository $vendorRepository)
    {
        $this->vendorRepository = $vendorRepository;
    }

    /**
     * Validate vendor payload
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function validateVendorPayload(array $data)
    {
        $validator = Validator::make($data, [
            'vendor_code' => 'required|string|max:255',
            'vendor_name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'responsible_person' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new Exception('Vendor validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validated();
    }

    /**
     * Create or update vendor
     *
     * @param array $data
     * @return IwexaVendor
     */
    public function createOrUpdateVendor(array $data)
    {
        $validatedData = $this->validateVendorPayload($data);

        // Store original payload
        $validatedData['original_iwexa_payload'] = $data;

        return $this->vendorRepository->upsert($validatedData);
    }

    /**
     * Find vendor by vendor_code
     *
     * @param string $vendorCode
     * @return IwexaVendor|null
     */
    public function findVendorByCode($vendorCode)
    {
        return $this->vendorRepository->findByVendorCode($vendorCode);
    }

    /**
     * Check if vendor exists by vendor_code
     *
     * @param string $vendorCode
     * @return bool
     */
    public function vendorExists($vendorCode)
    {
        return $this->vendorRepository->existsByVendorCode($vendorCode);
    }

    /**
     * Get all active vendors
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveVendors()
    {
        return $this->vendorRepository->getActiveVendors();
    }
}