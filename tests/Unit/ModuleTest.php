<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Facade;

class ModuleTest extends TestCase
{
    /** @test */
    public function it_contains_user_module()
    {
        // Now you can use the Module facade
        $modules = \Nwidart\Modules\Facades\Module::all(); // or use the facade class if imported
        $this->assertCount(6, $modules);
    }
}
