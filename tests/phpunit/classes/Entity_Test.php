<?php
/**
 * ORM
 *
 * Модульные тесты
 *
 * @version ${product.version}
 *
 * @copyright 2011, Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @package ORM
 * @subpackage Tests
 *
 * $Id: bootstrap.php 1849 2011-10-03 17:34:22Z mk $
 */


require_once __DIR__ . '/../bootstrap.php';
require_once TESTS_SRC_DIR . '/orm.php';
require_once TESTS_SRC_DIR . '/orm/classes/Entity.php';

/**
 * @package ORM
 * @subpackage Tests
 */
class ORM_Entity_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ORM_Entity::__construct
     * @covers ORM_Entity::getProperty
     * @covers ORM_Entity::setProperty
     * @covers ORM_Entity::__get
     * @covers ORM_Entity::__set
     */
    public function testOverview()
    {
        $entity = $this->getMockBuilder('ORM_Entity')->disableOriginalConstructor()->
            setMethods(array('getFoo', 'setFoo', 'getTable'))->getMock();
        $entity->expects($this->once())->method('getFoo')->will($this->returnValue('baz'));
        $entity->expects($this->once())->method('setFoo')->with('baz');
        $entity->expects($this->any())->method('getTable')
            ->will($this->returnValue(new \Mekras\TestDoubles\UniversalStub()));
        $EresusPlugin = 'Plugin'; // Обманываем IDEA
        $plugin = new $EresusPlugin;
        $attrs = array('foo' => 'bar');

        /** @var ORM_Entity $entity */
        $entity->__construct($plugin, $attrs);

        $p_plugin = new ReflectionProperty('ORM_Entity', 'plugin');
        $p_plugin->setAccessible(true);
        $this->assertSame($plugin, $p_plugin->getValue($entity));

        $p_attrs = new ReflectionProperty('ORM_Entity', 'attrs');
        $p_attrs->setAccessible(true);
        $this->assertEquals($attrs, $p_attrs->getValue($entity));

        $this->assertEquals('bar', $entity->getProperty('foo'));
        $this->assertNull($entity->getProperty('bar'));
        $entity->setProperty('bar', 'foo');
        $this->assertEquals('foo', $entity->getProperty('bar'));

        $this->assertEquals('baz', $entity->foo);
        $entity->foo = 'baz';
        $this->assertEquals('foo', $entity->bar);
        $entity->bar = 'foo';
    }

    /**
     * @covers ORM_Entity::getTable
     */
    public function testGetTable()
    {
        $entity = $this->getMockForAbstractClass('ORM_Entity', array(new Plugin),
            'ORM_Entity_Test__Entity_GetTable');

        $p_tables = new ReflectionProperty('ORM', 'tables');
        $p_tables->setAccessible(true);
        $p_tables->setValue('ORM', array('Plugin_Entity_Table_GetTable' => true));

        $this->assertTrue($entity->getTable());
    }

    /**
     * @covers ORM_Entity::getProperty
     */
    public function testGetProperty()
    {
        $table = $this->getMock('stdClass', array('getColumns', 'find'));
        $table->expects($this->any())->method('getColumns')
            ->will($this->returnValue(array(
                'foo' => array('type' => 'entity', 'class' => 'stdClass'),
            )));
        $table->expects($this->once())->method('find')->will($this->returnValue('object'));
        $entity = $this->getMockBuilder('ORM_Entity')->disableOriginalConstructor()
            ->setMethods(array('getTable'))->getMock();
        $entity->expects($this->any())->method('getTable')->will($this->returnValue($table));
        $Eresus_Plugin = 'Plugin'; // Обманываем IDEA
        $plugin = new $Eresus_Plugin;
        $attrs = array('foo' => 123);

        /** @var ORM_Entity $entity */
        $entity->__construct($plugin, $attrs);

        $attrsProperty = new ReflectionProperty('ORM_Entity', 'attrs');
        $attrsProperty->setAccessible(true);
        $this->assertEquals($attrs, $attrsProperty->getValue($entity));

        $legacyKernel = new stdClass();
        $legacyKernel->plugins = $this->getMock('stdClass', array('load'));
        $legacyKernel->plugins->expects($this->any())->method('load')
            ->will($this->returnValue(new $Eresus_Plugin));
        $app = $this->getMock('stdClass', array('getLegacyKernel'));
        $app->expects($this->any())->method('getLegacyKernel')
            ->will($this->returnValue($legacyKernel));
        $kernel = $this->getMock('stdClass', array('app'));
        $kernel->expects($this->any())->method('app')->will($this->returnValue($app));
        Eresus_Kernel::setMock($kernel);

        $tables = new ReflectionProperty('ORM', 'tables');
        $tables->setAccessible(true);
        $tables->setValue(array(
            'Plugin_Entity_Table_tdClass' => $table,
        ));

        $this->assertEquals('object', $entity->getProperty('foo'));
    }

    /**
     * @covers ORM_Entity::setProperty
     */
    public function testSetProperty()
    {
        $table = $this->getMock('stdClass', array('getColumns', 'getPrimaryKey'));
        $table->expects($this->any())->method('getColumns')
            ->will($this->returnValue(array(
                'foo' => array('type' => 'entity', 'class' => 'stdClass'),
            )));
        $table->expects($this->any())->method('getPrimaryKey')->will($this->returnValue('id'));
        $entity = $this->getMockBuilder('ORM_Entity')->disableOriginalConstructor()
            ->setMethods(array('getTable'))->getMock();
        $entity->expects($this->any())->method('getTable')->will($this->returnValue($table));

        $attrsProperty = new ReflectionProperty('ORM_Entity', 'attrs');
        $attrsProperty->setAccessible(true);

        $obj = new stdClass();
        $obj->id = 123;
        /** @var ORM_Entity $entity */
        $entity->setProperty('foo', $obj);
        $this->assertEquals(array('foo' => 123), $attrsProperty->getValue($entity));
    }

    /**
     * @covers ORM_Entity::getPrimaryKey
     */
    public function testGetPrimaryKey()
    {
        $plugin = $this->getMockBuilder('Plugin')->disableOriginalConstructor()
            ->setMockClassName('testGetPrimaryKey')->getMock();
        $entity = $this->getMockBuilder('ORM_Entity')->setMethods(array('_'))
            ->setMockClassName('testGetPrimaryKey_Entity_Bar')
            ->setConstructorArgs(array($plugin))->getMock();
        $attrs = new ReflectionProperty('ORM_Entity', 'attrs');
        $attrs->setAccessible(true);
        $attrs->setValue($entity, array('id' => 123));

        $legacyKernel = new stdClass();
        $legacyKernel->plugins = $this->getMock('stdClass', array('load'));
        $legacyKernel->plugins->expects($this->any())->method('load')
            ->will($this->returnValue($plugin));
        $app = $this->getMock('stdClass', array('getLegacyKernel'));
        $app->expects($this->any())->method('getLegacyKernel')
            ->will($this->returnValue($legacyKernel));
        $kernel = $this->getMock('stdClass', array('app'));
        $kernel->expects($this->any())->method('app')->will($this->returnValue($app));
        Eresus_Kernel::setMock($kernel);

        $table = $this->getMockBuilder('stdClass')
            ->setMethods(array('getPrimaryKey', 'getColumns'))
            ->setMockClassName('testGetPrimaryKey_Entity_Table_Bar')->getMock();
        $table->expects($this->any())->method('getPrimaryKey')->will($this->returnValue('id'));
        $table->expects($this->any())->method('getColumns')->will($this->returnValue(array()));
        $tables = new ReflectionProperty('ORM', 'tables');
        $tables->setAccessible(true);
        $tables->setValue(array('testGetPrimaryKey_Entity_Table_Bar' => $table));

        /** @var ORM_Entity $entity */
        $this->assertEquals(123, $entity->getPrimaryKey());
    }

    /**
     * @covers ORM_Entity::getTableByEntityClass
     */
    public function testGetTableByEntityClass()
    {
        $getTableByEntityClass = new ReflectionMethod('ORM_Entity', 'getTableByEntityClass');
        $getTableByEntityClass->setAccessible(true);

        $entity = $this->getMockBuilder('ORM_Entity')->disableOriginalConstructor()->getMock();

        $plugin = $this->getMockBuilder('Plugin')->disableOriginalConstructor()
            ->setMockClassName('Foo')->getMock();
        $legacyKernel = new stdClass();
        $legacyKernel->plugins = $this->getMock('stdClass', array('load'));
        $legacyKernel->plugins->expects($this->any())->method('load')
            ->will($this->returnValue($plugin));
        $app = $this->getMock('stdClass', array('getLegacyKernel'));
        $app->expects($this->any())->method('getLegacyKernel')
            ->will($this->returnValue($legacyKernel));
        $kernel = $this->getMock('stdClass', array('app'));
        $kernel->expects($this->any())->method('app')->will($this->returnValue($app));
        Eresus_Kernel::setMock($kernel);

        $this->getMockBuilder('stdClass')->setMockClassName('Foo_Entity_Table_Bar')->getMock();
        $this->assertInstanceOf('Foo_Entity_Table_Bar',
            $getTableByEntityClass->invoke($entity, 'Foo_Entity_Bar'));
    }
}

