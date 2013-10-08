<?php
/**
 * ORM
 *
 * Простое объектно-реляционное отображение для Eresus.
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
 */

/**
 * Основной класс плагина
 *
 * @package ORM
 */
class ORM extends Plugin
{
    /**
     * Версия плагина
     * @var string
     */
    public $version = '${product.version}';

    /**
     * Требуемая версия ядра
     * @var string
     */
    public $kernel = '3.00';

    /**
     * Название плагина
     * @var string
     */
    public $title = 'ORM';

    /**
     * Опиание плагина
     * @var string
     */
    public $description = 'Средства ORM для использования в других плагинах';

    /**
     * Драйвер СУБД
     * @var ORM_Driver_SQL
     * @since 2.02
     */
    private static $driver = null;

    /**
     * Реестр таблиц
     *
     * @var ORM_Table[]
     * @since 1.00
     */
    private static $tables = array();

    /**
     * Типы полей
     *
     * @var array
     * @since 1.00
     */
    private $filedTypes = array(
        'bindings' => 'ORM_FieldType_Bindings',
        'boolean' => 'ORM_FieldType_Boolean',
        'date' => 'ORM_FieldType_Date',
        'datetime' => 'ORM_FieldType_Datetime',
        'entity' => 'ORM_FieldType_Entity',
        'entities' => 'ORM_FieldType_Entities',
        'float' => 'ORM_FieldType_Float',
        'integer' => 'ORM_Field_Integer',
        'string' => 'ORM_FieldType_String',
        'timestamp' => 'ORM_FieldType_Timestamp'
    );

    /**
     * Конструктор
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Задаёт используемый драйвер СУБД
     *
     * @param ORM_Driver_SQL $driver
     *
     * @since 2.02
     */
    public static function setDriver(ORM_Driver_SQL $driver)
    {
        self::$driver = $driver;
    }

    /**
     * Возвращает используемый драйвер СУБД
     *
     * @return ORM_Driver_SQL
     *
     * @since 2.02
     */
    public static function getDriver()
    {
        if (null === self::$driver)
        {
            /** @var ORM $instance */
            $instance = Eresus_Kernel::app()->getLegacyKernel()->plugins->load('orm');
            self::$driver = new ORM_Driver_MySQL($instance);
        }
        return self::$driver;
    }

    /**
     * Возвращает объект таблицы для указанной сущности указанного плагина
     *
     * @param Plugin|TPlugin $plugin      плагин, которому принадлежит сущность
     * @param string         $entityName  имя сущности (без имени плагина и слова «Entity»)
     *
     * @return ORM_Table
     *
     * @throws InvalidArgumentException
     *
     * @since 1.00
     */
    public static function getTable($plugin, $entityName)
    {
        if (!($plugin instanceof Plugin) && !($plugin instanceof TPlugin))
        {
            throw new InvalidArgumentException(
                '$plugin must be Eresus_Plugin or TPlugin instance.'
            );
        }
        $className = get_class($plugin);
        if ($plugin instanceof TPlugin)
        {
            // Удаляем букву «T» из начала имени класса
            $className = substr($className, 1);
        }
        $className .= '_Entity_Table_' . $entityName;
        if (!isset(self::$tables[$className]))
        {
            self::$tables[$className] = new $className(self::getDriver(), $plugin);
        }
        return self::$tables[$className];
    }

    /**
     * Возвращает возможные типы полей
     *
     * @return ORM_Field_Abstract[]
     *
     * @since 2.02
     */
    public function getFieldTypes()
    {
        return $this->filedTypes;
    }

    /**
     * Возвращает возможные типы полей
     *
     * @return array
     *
     * @since 1.00
     * @deprecated с 2.02 используйте {@link getFieldTypes()}
     */
    public static function fieldTypes()
    {
        /** @var ORM $instance */
        $instance = Eresus_Kernel::app()->getLegacyKernel()->plugins->load('orm');
        return $instance->getFieldTypes();
    }

    /**
     * Регистрирует тип поля
     *
     * $typeClass должен быть потомком ORM_Field_Abstract и содержать в имени строку «_Field_».
     * Также должен существовать класс унаследованный от X, имя которого совпадает с $typeClass,
     * но строка «_Field_» заменена на «_Driver_SQL_»
     *
     * @param string $typeName   имя типа (латинские буквы в нижнем регистре и цифры)
     * @param string $typeClass  имя класса типа
     *
     * @since 2.02
     */
    public function registerFieldType($typeName, $typeClass)
    {
        $this->filedTypes[$typeName] = $typeClass;
    }

    /**
     * Возвращает таблицу по имени класса сущности
     *
     * @param string $entityClass
     *
     * @throws InvalidArgumentException
     *
     * @return ORM_Table
     *
     * @since 2.02
     */
    public function getTableByEntityClass($entityClass)
    {
        if ('' === strval($entityClass))
        {
            throw new InvalidArgumentException('$entityClass can not be blank');
        }
        $entityPluginName = substr($entityClass, 0, strpos($entityClass, '_'));
        $entityPluginName = strtolower($entityPluginName);
        $plugin = Eresus_Kernel::app()->getLegacyKernel()->plugins
            ->load($entityPluginName);
        $entityName = substr($entityClass, strrpos($entityClass, '_') + 1);
        $table = self::getTable($plugin, $entityName);
        return $table;
    }
}

