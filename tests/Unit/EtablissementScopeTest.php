<?php

// Standalone test script for EtablissementScope
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Support\EtablissementScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EtablissementScopeTest
{
    public function run()
    {
        $methods = get_class_methods($this);
        $passed = 0;
        $failed = 0;

        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                // Run each test within a transaction to isolate test data seeding
                DB::beginTransaction();
                // Disable foreign key checks to allow seeding without breaking legacy constraints
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                try {
                    // Clear cache before each test to ensure fresh queries
                    Cache::flush();
                    
                    $this->$method();
                    echo "✓ {$method} PASSED\n";
                    $passed++;
                } catch (\Throwable $e) {
                    echo "✗ {$method} FAILED: " . $e->getMessage() . "\n";
                    echo "  File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
                    $failed++;
                } finally {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    DB::rollBack();
                }
            }
        }

        echo "\n=========================================\n";
        echo "Total Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
        echo "=========================================\n";
        
        exit($failed > 0 ? 1 : 0);
    }

    private function assertEquals($expected, $actual, $msg = '')
    {
        sort($expected);
        sort($actual);
        if ($expected !== $actual) {
            throw new \Exception("Expected " . json_encode($expected) . ", got " . json_encode($actual) . ". {$msg}");
        }
    }

    /**
     * Test scoping for a public center: INSFP Setif (352) and its supervised schools.
     */
    public function testResolvePublicCenterSetif()
    {
        // Update or insert public center 352
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 352],
            ['Nom' => 'INSFP Setif', 'PublPrive' => 0, 'activee' => 0]
        );
        // Update or insert private school 1301 supervised by 352
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 1301],
            ['Nom' => 'Taj Al-Azraq', 'PublPrive' => 1, 'DeIDetablissementRatache' => 352, 'activee' => 0]
        );
        // Update or insert private school 1302 supervised by 352
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 1302],
            ['Nom' => 'Bacha School', 'PublPrive' => 1, 'DeIDetablissementRatacheInsfp' => 352, 'activee' => 0]
        );

        $scope = EtablissementScope::resolve(352);
        
        // Use in_array checks because the actual database might already contain other supervised schools
        if (!in_array(352, $scope)) {
            throw new \Exception("Scope does not contain the public center itself (352).");
        }
        if (!in_array(1301, $scope)) {
            throw new \Exception("Scope does not contain the supervised private school Taj Al-Azraq (1301).");
        }
        if (!in_array(1302, $scope)) {
            throw new \Exception("Scope does not contain the supervised private school Bacha School (1302).");
        }
    }

    /**
     * Test scoping for a private school: Taj Al-Azraq (1301)
     */
    public function testResolvePrivateSchoolTaj()
    {
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 1301],
            ['Nom' => 'Taj Al-Azraq', 'PublPrive' => 1, 'DeIDetablissementRatache' => 352, 'activee' => 0]
        );

        $scope = EtablissementScope::resolve(1301);
        
        // A private school must only see itself (strict isolation)
        $this->assertEquals([1301], $scope, "Taj Al-Azraq should strictly only see itself.");
    }

    /**
     * Test invalid inputs: 999999, 0, null
     */
    public function testResolveInvalidInputs()
    {
        $this->assertEquals([], EtablissementScope::resolve(999999), "Invalid ID should return empty array.");
        $this->assertEquals([], EtablissementScope::resolve(0), "ID 0 should return empty array.");
        $this->assertEquals([], EtablissementScope::resolve(null), "Null ID should return empty array.");
    }

    /**
     * Test cycle detection: when B points to A and A points to B
     */
    public function testResolveCycleDetection()
    {
        // A (ID 7001) is public and points to B (ID 7002) as parent coordinator
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 7001],
            ['Nom' => 'Etab A', 'PublPrive' => 0, 'IDEts_Form' => 7002, 'activee' => 0]
        );
        // B (ID 7002) is public and points to A (ID 7001) as parent coordinator
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 7002],
            ['Nom' => 'Etab B', 'PublPrive' => 0, 'IDEts_Form' => 7001, 'activee' => 0]
        );

        $scopeA = EtablissementScope::resolve(7001);
        $scopeB = EtablissementScope::resolve(7002);

        $this->assertEquals([7001, 7002], $scopeA, "Cycle detection should prevent infinite recursion and return A and B.");
        $this->assertEquals([7001, 7002], $scopeB, "Cycle detection should prevent infinite recursion and return A and B.");
    }

    /**
     * Test caching behavior: second call should be resolved from Cache
     */
    public function testResolveCachePerformance()
    {
        DB::table('etablissement')->updateOrInsert(
            ['IDetablissement' => 1301],
            ['Nom' => 'Taj Al-Azraq', 'PublPrive' => 1, 'activee' => 0]
        );

        // 1. Resolve to warm up cache
        $scope1 = EtablissementScope::resolve(1301);
        
        // 2. Temporarily change PublPrive in DB inside transaction.
        // If caching is working, it should still return the cached array [1301]
        DB::table('etablissement')->where('IDetablissement', 1301)->update(['PublPrive' => 0]);
        
        $scope2 = EtablissementScope::resolve(1301);
        $this->assertEquals($scope1, $scope2, "Cache should store and return the resolved scope without querying DB.");
    }
}

$test = new EtablissementScopeTest();
$test->run();
