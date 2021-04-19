<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Widgets\WidgetFactory;

class CopyWidgetTest extends TestCase
{
    /**
     * @group testNameHelper
     * Tests widget copy name
     */
    public function testNameHelperMethod()
    {

        $testWidget = (new WidgetFactory())->createWidget('text');


        // Корректно обрабатывает регулярные случаи
        $this->assertEquals(
            'Название (копия 1)',
            $testWidget->getCopyName('Название')
        );

        $this->assertEquals(
            'test (копия 1)',
            $testWidget->getCopyName('test')
        );

        $this->assertEquals(
            'Название (копия 2)',
            $testWidget->getCopyName('Название (копия 1)')
        );

        $this->assertEquals(
            'Название (копия 2)',
            $testWidget->getCopyName('Название(копия 1)')
        );

        $this->assertEquals(
            'Название (копия 3)',
            $testWidget->getCopyName('Название (копия 2)')
        );
    }

    /**
     * @group testNameHelperCorners
     * Tests widget copy name corners
     */
    public function testNameHelperMethodCornerCases()
    {
        // Корректно обрабатывает крайние случаи
        
        $testWidget = (new WidgetFactory())->createWidget('text');

        $this->assertEquals(
            '(копия 1)',
            $testWidget->getCopyName('')
        );
        $this->assertEquals(
            '(копия 2)',
            $testWidget->getCopyName('(копия 1)')
        );
        $this->assertEquals(
            '(копия 1) (копия 2)',
            $testWidget->getCopyName('(копия 1)(копия 1)')
        );
    }
}