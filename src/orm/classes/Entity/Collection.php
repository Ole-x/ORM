<?php
/**
 * Коллекция сущностей
 *
 * @version ${product.version}
 *
 * @copyright 2013, Михаил Красильников <m.krasilnikov@yandex.ru>
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
 * Коллекция сущностей
 *
 * @package ORM
 * @since unstable
 */
class ORM_Entity_Collection extends SplObjectStorage
{
    /**
     * @param ORM_Entity $entity
     * @return bool
     */
    public function contains(ORM_Entity $entity)
    {
        if ($entity->getEntityState() == $entity::IS_DELETED)
        {
            return false;
        }
        return parent::contains($entity);
    }

    /**
     * @return int
     */
    public function count()
    {
        $count = 0;
        foreach ($this as $entity)
        {
            /** @var ORM_Entity $entity */
            if ($entity->getEntityState() != $entity::IS_DELETED)
            {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return void
     */
    public function next()
    {
        do
        {
            parent::next();
        }
        while ($this->valid() && $this->isCurrentEntityDeleted());
    }

    /**
     * @return void
     */
    public function rewind()
    {
        parent::rewind();
        if ($this->valid() && $this->isCurrentEntityDeleted())
        {
            $this->next();
        }
    }

    /**
     * Возвращает true если текущая сущность помечена как удалённая
     * @return bool
     */
    private function isCurrentEntityDeleted()
    {
        /** @var ORM_Entity $entity */
        $entity = $this->current();
        $isCurrentEntityDeleted = $entity->getEntityState() == ORM_Entity::IS_DELETED;
        return $isCurrentEntityDeleted;
    }
}

