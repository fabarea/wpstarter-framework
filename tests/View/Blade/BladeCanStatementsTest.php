<?php

namespace WpStarter\Tests\View\Blade;

class BladeCanStatementsTest extends AbstractBladeTestCase
{
    public function testCanStatementsAreCompiled()
    {
        $string = '@can (\'update\', [$post])
breeze
@elsecan(\'delete\', [$post])
sneeze
@endcan';
        $expected = '<?php if (ws_app(\\WpStarter\\Contracts\\Auth\\Access\\Gate::class)->check(\'update\', [$post])): ?>
breeze
<?php elseif (ws_app(\\WpStarter\\Contracts\\Auth\\Access\\Gate::class)->check(\'delete\', [$post])): ?>
sneeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
