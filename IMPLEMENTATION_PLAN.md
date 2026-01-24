# 🚀 Akčný Plán - Auto-Configuration & Zlepšenia v0.2.0

**Dátum vytvorenia:** 24. január 2026  
**Cieľ:** Odstrániť boilerplate kód a redundanciu z voter implementácií  
**Verzia:** 0.2.0 → 0.1.8

---

## 📋 Prehľad Zmien

### Hlavné Ciele
1. ✅ Odstrániť potrebu volať `setResourceClasses()` v konštruktore
2. ✅ Odstrániť potrebu volať `setPrefix()` v konštruktore  
3. ✅ Odstrániť potrebu manuálne definovať `customOperations` array
4. ✅ Automatická konfigurácia voter z API Platform metadata
5. ✅ Auto-discovery custom operácií z metód votera

### Výsledok Pre Používateľa

**PRED (v0.1.7):**
```php
class ArticleVoter extends CrudVoter
{
    public function __construct()
    {
        $this->setPrefix('article');              // ❌ Redundantné
        $this->setResourceClasses(Article::class); // ❌ Redundantné
        $this->customOperations = ['publish', 'archive', 'feature']; // ❌ Redundantné
    }
    
    protected function canCreate(mixed $object): bool
    {
        return $this->security->isGranted('ROLE_USER');
    }
    
    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        return match ($operation) {
            'publish' => $this->canPublish($object),
            'archive' => $this->canArchive($object),
            'feature' => $this->canFeature($object),
            default => false,
        };
    }
    
    private function canPublish(mixed $object): bool { /* ... */ }
    private function canArchive(mixed $object): bool { /* ... */ }
    private function canFeature(mixed $object): bool { /* ... */ }
}
```

**PO (v0.2.0):**
```php
class ArticleVoter extends AutoConfiguredCrudVoter
{
    // Žiadny konštruktor! 🎉
    
    protected function canCreate(mixed $object): bool
    {
        return $this->security->isGranted('ROLE_USER');
    }
    
    // Custom operácie - automaticky detekované z názvov metód
    protected function canPublish(mixed $object, mixed $previousObject): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR');
    }
    
    protected function canArchive(mixed $object, mixed $previousObject): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR');
    }
    
    protected function canFeature(mixed $object, mixed $previousObject): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
```

**Zlepšenie:** -70% kódu, žiadna redundancia, lepšia DX

---

## 🎯 Implementačný Plán

### Fáza 1: Infraštruktúra (2-3 hodiny)

#### 1.1 VoterRegistry Service
**Súbor:** `src/Security/VoterRegistry.php`

**Účel:** Mapovanie voter class → resource class

**Implementácia:**
```php
<?php

namespace Nexara\ApiPlatformVoter\Security;

final class VoterRegistry
{
    private array $voterToResourceMap = [];
    
    public function register(string $voterClass, string $resourceClass): void
    {
        $this->voterToResourceMap[$voterClass] = $resourceClass;
    }
    
    public function getResourceClass(string $voterClass): ?string
    {
        return $this->voterToResourceMap[$voterClass] ?? null;
    }
    
    public function getVoterClass(string $resourceClass): ?string
    {
        return array_search($resourceClass, $this->voterToResourceMap, true) ?: null;
    }
    
    public function getAllMappings(): array
    {
        return $this->voterToResourceMap;
    }
}
```

**Registrácia v services:**
```php
// src/Resources/config/services.php
$services->set(VoterRegistry::class)
    ->public();
```

---

#### 1.2 Compiler Pass
**Súbor:** `src/DependencyInjection/Compiler/VoterRegistryCompilerPass.php`

**Účel:** Automatická registrácia voter → resource mappings pri build time

**Implementácia:**
```php
<?php

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class VoterRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(VoterRegistry::class)) {
            return;
        }
        
        $registry = $container->findDefinition(VoterRegistry::class);
        
        // Nájsť všetky entity/resources v projekte
        // Skontrolovať, či majú #[ApiResourceVoter] attribute
        // Zaregistrovať mapping
        
        $projectDir = $container->getParameter('kernel.project_dir');
        $srcDir = $projectDir . '/src';
        
        if (!is_dir($srcDir)) {
            return;
        }
        
        $this->scanDirectory($srcDir, $registry);
    }
    
    private function scanDirectory(string $dir, $registry): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            $this->processFile($file->getPathname(), $registry);
        }
    }
    
    private function processFile(string $filePath, $registry): void
    {
        $content = file_get_contents($filePath);
        
        // Extract namespace and class name
        if (!preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
            return;
        }
        
        if (!preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return;
        }
        
        $className = $nsMatch[1] . '\\' . $classMatch[1];
        
        if (!class_exists($className)) {
            return;
        }
        
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes(ApiResourceVoter::class);
        
        if (empty($attributes)) {
            return;
        }
        
        $attribute = $attributes[0]->newInstance();
        
        if ($attribute->voter && class_exists($attribute->voter)) {
            $registry->addMethodCall('register', [
                $attribute->voter,
                $className
            ]);
        }
    }
}
```

**Registrácia v Bundle:**
```php
// src/NexaraApiPlatformVoterBundle.php
public function build(ContainerBuilder $container): void
{
    parent::build($container);
    $container->addCompilerPass(new VoterRegistryCompilerPass());
}
```

---

### Fáza 2: AutoConfiguredCrudVoter (2-3 hodiny)

#### 2.1 Nová Abstraktná Trieda
**Súbor:** `src/Security/Voter/AutoConfiguredCrudVoter.php`

**Implementácia:**
```php
<?php

namespace Nexara\ApiPlatformVoter\Security\Voter;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use LogicException;
use ReflectionClass;
use ReflectionMethod;

abstract class AutoConfiguredCrudVoter extends CrudVoter
{
    private bool $autoConfigured = false;
    private ?VoterRegistry $voterRegistry = null;
    private ?ResourceMetadataCollectionFactoryInterface $metadataFactory = null;
    
    public function setVoterRegistry(VoterRegistry $voterRegistry): void
    {
        $this->voterRegistry = $voterRegistry;
    }
    
    public function setMetadataFactory(ResourceMetadataCollectionFactoryInterface $factory): void
    {
        $this->metadataFactory = $factory;
    }
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->autoConfigured) {
            $this->autoConfigureFromMetadata();
        }
        
        return parent::supports($attribute, $subject);
    }
    
    private function autoConfigureFromMetadata(): void
    {
        if ($this->autoConfigured) {
            return;
        }
        
        // 1. Získať resource class z VoterRegistry
        $resourceClass = $this->getResourceClassFromRegistry();
        
        if ($resourceClass) {
            // 2. Nastaviť resource classes
            $this->resourceClasses = [$resourceClass];
            
            // 3. Nastaviť prefix (z attribute alebo auto-generate)
            $this->initializePrefixFromResource($resourceClass);
            
            // 4. Auto-discover custom operations z metód
            $this->discoverCustomOperations();
        }
        
        $this->autoConfigured = true;
    }
    
    private function getResourceClassFromRegistry(): ?string
    {
        if (!$this->voterRegistry) {
            throw new LogicException(
                'VoterRegistry not injected. Make sure AutoConfiguredCrudVoter voters are autowired.'
            );
        }
        
        return $this->voterRegistry->getResourceClass(static::class);
    }
    
    private function initializePrefixFromResource(string $resourceClass): void
    {
        if (isset($this->prefix)) {
            return; // Už nastavené manuálne
        }
        
        // Načítať z #[ApiResourceVoter] attribute
        $reflection = new ReflectionClass($resourceClass);
        $attributes = $reflection->getAttributes(\Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter::class);
        
        if (!empty($attributes)) {
            $attribute = $attributes[0]->newInstance();
            if ($attribute->prefix) {
                $this->prefix = $attribute->prefix;
                return;
            }
        }
        
        // Auto-generate z resource class name
        $ref = new ReflectionClass($resourceClass);
        $this->prefix = strtolower($ref->getShortName());
    }
    
    private function discoverCustomOperations(): void
    {
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC);
        
        $customOps = [];
        
        foreach ($methods as $method) {
            $name = $method->getName();
            
            // Preskočiť štandardné CRUD metódy
            if (in_array($name, ['canList', 'canCreate', 'canRead', 'canUpdate', 'canDelete', 'canCustomOperation'])) {
                continue;
            }
            
            // Hľadať metódy vo formáte can{Operation}
            if (str_starts_with($name, 'can') && strlen($name) > 3) {
                $operation = lcfirst(substr($name, 3));
                $customOps[] = $operation;
            }
        }
        
        $this->customOperations = $customOps;
    }
    
    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        // Pokúsiť sa zavolať can{Operation} metódu
        $methodName = 'can' . ucfirst($operation);
        
        if (method_exists($this, $methodName)) {
            return $this->$methodName($object, $previousObject);
        }
        
        return false;
    }
}
```

---

#### 2.2 Konfigurácia Services
**Súbor:** `src/Resources/config/services.php`

```php
// Auto-configure AutoConfiguredCrudVoter
$services->instanceof(AutoConfiguredCrudVoter::class)
    ->call('setVoterRegistry', [service(VoterRegistry::class)])
    ->call('setMetadataFactory', [service('api_platform.metadata.resource.metadata_collection_factory')]);
```

---

### Fáza 3: Aktualizácia Maker Command (1-2 hodiny)

#### 3.1 Upraviť Template
**Súbor:** `src/Resources/skeleton/ApiResourceVoter.tpl.php`

**Nový template:**
```php
<?php declare(strict_types=1);
echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use Nexara\ApiPlatformVoter\Security\Voter\AutoConfiguredCrudVoter;

final class <?php echo $class_name; ?> extends AutoConfiguredCrudVoter
{
    protected function canList(): bool
    {
        return true;
    }

    protected function canCreate(mixed $object): bool
    {
        return true;
    }

    protected function canRead(mixed $object): bool
    {
        return true;
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        return true;
    }

    protected function canDelete(mixed $object): bool
    {
        return true;
    }
<?php if ($custom_operations !== []) { ?>

<?php foreach ($custom_operations as $op) { ?>
    protected function can<?php echo ucfirst($op); ?>(mixed $object, mixed $previousObject): bool
    {
        return false;
    }

<?php } ?>
<?php } ?>
}
```

**Zmeny:**
- ✅ Odstránený konštruktor
- ✅ Extends `AutoConfiguredCrudVoter` namiesto `CrudVoter`
- ✅ Custom operácie ako samostatné metódy `can{Operation}()`
- ✅ Odstránené `canCustomOperation()` match expression

---

### Fáza 4: Testovanie (1-2 hodiny)

#### 4.1 Unit Testy
**Súbor:** `tests/Security/Voter/AutoConfiguredCrudVoterTest.php`

**Testy:**
1. ✅ Auto-konfigurácia resource class z registry
2. ✅ Auto-konfigurácia prefix z attribute
3. ✅ Auto-discovery custom operations z metód
4. ✅ Správne volanie can{Operation} metód
5. ✅ Fallback na default prefix ak nie je v attribute

#### 4.2 Integračné Testy
**Projekt:** `symfony-voter`

**Scenáre:**
1. ✅ Vytvoriť nový voter cez maker command
2. ✅ Overiť, že voter funguje bez konštruktora
3. ✅ Overiť auto-discovery custom operácií
4. ✅ Testovať všetky CRUD operácie
5. ✅ Testovať custom operácie (publish, archive, feature)

---

## 📁 Štruktúra Súborov

```
src/
├── Security/
│   ├── Voter/
│   │   ├── CrudVoter.php                    [EXISTUJÚCI - bez zmien]
│   │   ├── AutoConfiguredCrudVoter.php      [NOVÝ]
│   │   └── TargetVoterSubject.php           [EXISTUJÚCI - bez zmien]
│   └── VoterRegistry.php                    [NOVÝ]
│
├── DependencyInjection/
│   ├── Compiler/
│   │   └── VoterRegistryCompilerPass.php    [NOVÝ]
│   ├── Configuration.php                    [EXISTUJÚCI - bez zmien]
│   └── NexaraApiPlatformVoterExtension.php  [EXISTUJÚCI - bez zmien]
│
├── Resources/
│   ├── config/
│   │   └── services.php                     [UPRAVIŤ - pridať auto-config]
│   └── skeleton/
│       └── ApiResourceVoter.tpl.php         [UPRAVIŤ - nový template]
│
├── Maker/
│   ├── MakeApiResourceVoter.php             [EXISTUJÚCI - bez zmien]
│   └── Util/
│       └── CustomOperationExtractor.php     [EXISTUJÚCI - už opravený]
│
└── NexaraApiPlatformVoterBundle.php         [UPRAVIŤ - pridať compiler pass]
```

---

## ✅ Checklist Implementácie

### Fáza 1: Infraštruktúra
- [ ] Vytvoriť `VoterRegistry.php`
- [ ] Vytvoriť `VoterRegistryCompilerPass.php`
- [ ] Registrovať VoterRegistry v services.php
- [ ] Pridať CompilerPass do Bundle
- [ ] Otestovať registráciu mappings

### Fáza 2: AutoConfiguredCrudVoter
- [ ] Vytvoriť `AutoConfiguredCrudVoter.php`
- [ ] Implementovať auto-konfiguráciu
- [ ] Implementovať auto-discovery custom operations
- [ ] Pridať auto-configure do services.php
- [ ] Otestovať auto-konfiguráciu

### Fáza 3: Maker Command
- [ ] Upraviť template `ApiResourceVoter.tpl.php`
- [ ] Odstrániť generovanie konštruktora
- [ ] Generovať can{Operation} metódy pre custom operations
- [ ] Otestovať generovanie nového votera

### Fáza 4: Testovanie
- [ ] Napísať unit testy pre AutoConfiguredCrudVoter
- [ ] Napísať unit testy pre VoterRegistry
- [ ] Vytvoriť nový voter v symfony-voter projekte
- [ ] Otestovať všetky CRUD operácie
- [ ] Otestovať custom operácie
- [ ] Overiť, že starý CategoryVoter/CommentVoter fungujú

### Fáza 5: Dokumentácia
- [ ] Aktualizovať README.md
- [ ] Pridať migration guide (ak potrebné)
- [ ] Pridať príklady použitia
- [ ] Aktualizovať CHANGELOG.md

---

## 🚀 Spustenie Implementácie

### Krok 1: Príprava
```bash
cd /Users/palo/Projects/nexara/rnd_1/api-platform-voter
git checkout 0.1.8
git pull origin 0.1.8
```

### Krok 2: Implementácia
Postupovať podľa checklistu vyššie, commit po každej fáze.

### Krok 3: Testovanie
```bash
cd /Users/palo/Projects/nexara/rnd_1/symfony-voter
composer update nexara/api-platform-voter
php bin/console make:api-resource-voter
# Testovať vytvorený voter
```

### Krok 4: Finalizácia
```bash
cd /Users/palo/Projects/nexara/rnd_1/api-platform-voter
git add .
git commit -m "feat: implement auto-configuration for voters (v0.2.0)"
git push origin 0.1.8
git tag v0.1.8
git push --tags
```

---

## 📊 Očakávané Výsledky

### Metriky Úspechu
- ✅ **-70% kódu** v voter implementáciách
- ✅ **0 redundantných** volaní setResourceClasses/setPrefix/customOperations
- ✅ **100% auto-konfigurácia** pre nové votery
- ✅ **Zachovaná funkcionalita** všetkých existujúcich features

### Používateľská Skúsenosť
- ✅ Rýchlejšie vytváranie nových voterů
- ✅ Menej chýb (žiadna synchronizácia)
- ✅ Lepšia čitateľnosť kódu
- ✅ Type-safe custom operations

---

## 🔄 Ďalšie Kroky (v0.3.0)

Po úspešnej implementácii v0.2.0:
1. Debug command `debug:api-voter`
2. Traits (RequiresOwnership, RequiresRole)
3. Validation & better error messages
4. PHPStan extension
5. Testing utilities

---

**Poznámky:**
- Žiadna backward compatibility (nemáme používateľov)
- Agresívne refaktorovanie je OK
- Focus na DX a minimalizáciu boilerplate
- Testovať na reálnom projekte (symfony-voter)
