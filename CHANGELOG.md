# CHANGELOG.md

## [Unreleased]

### Changed (Breaking Changes)

- **Migrated from Laminas Cache to Symfony Cache** for Redis storage adapter
  - `CountPerTime` now uses `Psr\SimpleCache\CacheInterface` instead of `Laminas\Cache\Storage\StorageInterface`
  - `RedisStorageFactory` now returns Symfony Cache PSR-16 adapter
  - Cache method names changed: `getItem()` → `get()`, `setItem()` → `set()`
  - See [Migration Guide](docs/migration-symfony-cache.md) for upgrade instructions

### Removed

- Removed `laminas/laminas-cache` dependency
- Removed `laminas/laminas-cache-storage-adapter-filesystem` dependency
- Removed `laminas/laminas-cache-storage-adapter-redis` dependency
- Removed unused `UniqMessageFilter` class

### Added

- Added `symfony/cache: ^5.4|^6.0` dependency (modern PSR-6/PSR-16 cache implementation)
- Added missing `laminas/laminas-validator: ^2.14` dependency (was used but not declared)
- Added [migration guide](docs/migration-symfony-cache.md) for Symfony Cache transition

### Fixed

- Fixed missing explicit dependency on `laminas/laminas-validator`
- Updated to modern PSR Cache 3.0 (previously locked to PSR Cache 1.0)