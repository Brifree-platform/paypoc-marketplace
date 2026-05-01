# PAYPOC Iwexa Connector Module

A Laravel/Bagisto package that integrates with Iwexa Hub for catalog synchronization, stock management, and webhook processing.

## Features

- **Catalog Import**: Batch product synchronization from Iwexa Hub
- **Stock Management**: Real-time stock updates and warehouse management
- **Webhook Processing**: Secure HMAC-validated webhook receiver
- **Attribute Mapping**: Flexible mapping of product attributes across systems
- **Category Mapping**: Source category to Bagisto category mapping with vendor overrides
- **Product Type Mapping**: Auto-provisioning of draft attribute families
- **Idempotency**: Guaranteed-once delivery via idempotency keys and webhook deduplication
- **Admin Dashboard**: Manage mappings, approve changes, monitor sync jobs

## Architecture

### Database
- 8 migrations covering sync jobs, products, stock logs, webhooks, and mapping tables
- Support for variant products with parent/child SKU relationships
- Original Iwexa payload storage for full traceability

### Services
- `IwexaApiService`: REST client for Iwexa Hub API
- `CatalogImportService`: Batch product import with validation
- `StockUpdateService`: Stock quantity and warehouse management
- `CategoryMappingService`: Category mapping with vendor overrides
- `ProductTypeMappingService`: Attribute family assignment
- `AttributeMappingService`: Attribute value normalization
- `AttributeProvisioningService`: Auto-creates draft mappings for unknown attributes
- `WebhookProcessorService`: Webhook validation and event routing

### Controllers
- **API Controllers** (3):
  - `CatalogController`: Product import endpoints
  - `StockController`: Stock update endpoints
  - `WebhookController`: Webhook receiver
  
- **Admin Controllers** (4):
  - `ProductTypeMappingController`: Approve/manage attribute families
  - `CategoryMappingController`: Manage category mappings
  - `AttributeMappingController`: Configure attribute mappings
  - `SyncJobController`: Monitor and retry sync jobs

### Queue Jobs
- `ProcessCatalogImport`: Async catalog import with retry logic
- `ProcessStockUpdate`: Async stock updates
- `ProcessWebhook`: Webhook processing queue
- `RetryFailedSyncJob`: Manual retry mechanism
- `ReleaseExpiredIdempotencyKeys`: Cleanup expired events

### Repositories
- `IwexaSyncJobRepository`: Sync job queries
- `IwexaProductRepository`: Product queries
- `CategoryMappingRepository`: Category mapping queries
- `ProductTypeMappingRepository`: Attribute family queries
- `AttributeMappingRepository`: Attribute mapping queries

## Installation

```bash
# Copy module to packages directory
cp -r IwexaConnector packages/PAYPOC/

# Publish migrations
php artisan vendor:publish --tag=iwexa-migrations

# Run migrations
php artisan migrate

# Publish config
php artisan vendor:publish --tag=iwexa-config
```

## Configuration

Create `.env` entries:

```env
IWEXA_API_BASE_URL=https://api.iwexa.com
IWEXA_API_KEY=your-api-key
IWEXA_HMAC_SECRET=your-hmac-secret
IWEXA_SYNC_JOB_RETRY_LIMIT=3
IWEXA_SYNC_JOB_RETRY_DELAY=5
IWEXA_IDEMPOTENCY_KEY_EXPIRY=24
IWEXA_WEBHOOK_EVENT_EXPIRY=24
IWEXA_BATCH_SIZE_PRODUCTS=100
IWEXA_BATCH_SIZE_STOCK=500
```

## API Endpoints

### Catalog
- **POST** `/bagisto-api/iwexa/catalog/products/batch` - Import products
  ```json
  {
    "products": [
      {
        "sku": "SKU-001",
        "vendor_code": "VENDOR-1",
        "product_type": "simple",
        "source_category": "Electronics",
        "parent_sku": null,
        "ean": "1234567890",
        "currency": "EUR"
      }
    ]
  }
  ```

- **PUT** `/bagisto-api/iwexa/catalog/products/{sku}` - Update product

### Stock
- **POST** `/bagisto-api/iwexa/catalog/stock` - Update stock
  ```json
  {
    "stock_updates": [
      {
        "sku": "SKU-001",
        "quantity": 100,
        "warehouse_code": "WH-1",
        "stock_status": "in_stock"
      }
    ]
  }
  ```

### Webhooks
- **POST** `/bagisto-api/iwexa/webhooks` - Receive webhooks

## Admin Routes

- `/admin/iwexa/product-type-mappings` - Manage attribute families
- `/admin/iwexa/category-mappings` - Manage category mappings
- `/admin/iwexa/attribute-mappings` - Manage attribute mappings
- `/admin/iwexa/sync-jobs` - Monitor sync jobs

## Database Schema

### iwexa_sync_jobs
Tracks all import/sync operations with idempotency enforcement.

### iwexa_products
Stores product records with original Iwexa payload for traceability.

### iwexa_stock_logs
Audit trail of all stock changes.

### iwexa_webhook_events
Webhook delivery tracking for idempotency.

### category_mappings
Maps source categories to Bagisto with vendor-specific overrides.

### product_type_mappings
Maps Iwexa product types to Bagisto attribute families.

### attribute_mappings
Maps individual attributes per product type.

### attribute_value_mappings
Maps attribute values (e.g., XL → Extra Large).

## Workflow

1. **Catalog Import**
   - Receive batch of products via API
   - Validate SKU, vendor code, currency
   - Check if mapping exists; create draft if not
   - Store original Iwexa payload
   - Mark product as `pending_mapping` if type unknown
   - Return import result

2. **Mapping Approval**
   - Admin reviews draft attribute families
   - Configures Bagisto attributes
   - Approves mapping (status → active)
   - Products transition to active

3. **Stock Sync**
   - Receive stock updates
   - Update product quantities
   - Log all changes to audit trail
   - Mark in/out of stock

4. **Webhook Processing**
   - Validate HMAC signature
   - Check event_id + delivery_id uniqueness (idempotency)
   - Route to handler (catalog.product.updated, catalog.stock.updated, etc.)
   - Log event for replay prevention

## Security

- **HMAC-SHA256**: All webhooks signed with secret key
- **Idempotency Keys**: Prevent duplicate processing
- **Bearer Token**: API authentication
- **Rate Limiting**: 60 requests per minute per IP

## Error Handling

- Failed syncs logged with error details
- Automatic retry on transient failures (max 3 attempts)
- Admin interface for manual retries
- Email notifications on critical failures (configurable)

## Development

### Scopes
Models include Laravel scopes for common queries:

```php
// Products
IwexaProduct::active()->get();
IwexaProduct::pendingMapping()->get();
IwexaProduct::byVendor('VENDOR-1')->get();
IwexaProduct::byProductType('simple')->get();

// Sync Jobs
IwexaSyncJob::byType('catalog_import')->get();
IwexaSyncJob::byStatus('failed')->get();
IwexaSyncJob::failed()->get();
```

### Events
Dispatch for custom listeners:

```php
event(new CatalogSyncStarted($syncJob));
event(new CatalogSyncCompleted($syncJob, $result));
event(new ProductMappingRequired($product));
```

## Future Enhancements

- [ ] Variant product image sync
- [ ] Multi-language product descriptions
- [ ] Google Merchant feed generation
- [ ] Amazon Marketplace integration
- [ ] Real-time inventory notifications
- [ ] Bulk attribute configuration
- [ ] Advanced filtering and search
- [ ] Performance analytics dashboard

## Support

For issues or questions, contact the PAYPOC development team.
