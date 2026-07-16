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
     * Test scoping for a public center: INSFP Setif (352)
     */
    public function testResolvePublicCenterSetif()
    {
        $scope = EtablissementScope::resolve(352);
        
        // It must contain itself (352) and the supervised private schools (e.g. Taj 1301)
        if (!in_array(352, $scope)) {
            throw new \Exception("Scope does not contain the public center itself (352).");
        }
        if (!in_array(1301, $scope)) {
            throw new \Exception("Scope does not contain the supervised private school Taj Al-Azraq (1301).");
        }
    }

    /**
     * Test scoping for a private school: Taj Al-Azraq (1301)
     */
    public function testResolvePrivateSchoolTaj()
    {
        $scope = EtablissementScope::resolve(1301);
        
        // A private school must only see itself (strict isolation)
        $this->assertEquals([1301], $scope, "Taj Al-Azraq should only see itself.");
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
     * Test caching behavior: second call should be resolved from Cache
     */
    public function testResolveCachePerformance()
    {
        // 1. Resolve to warm up cache
        $scope1 = EtablissementScope::resolve(1301);
        
        // 2. Temporarily change PublPrive in DB inside transaction.
        // If caching is working, it should still return the cached array [1301]
        // even though PublPrive is changed to 0 (which would otherwise include Setif schools if queried freshly).
        DB::beginTransaction();
        try {
            DB::table('etablissement')->where('IDetablissement', 1301)->update(['PublPrive' => 0]);
            
            $scope2 = EtablissementScope::resolve(1301);
            $this->assertEquals($scope1, $scope2, "Cache should store and return the resolved scope without querying DB.");
        } finally {
            DB::rollBack();
        }
    }
}

$test = new EtablissementScopeTest();
$test->run();
