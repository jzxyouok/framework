<?php
namespace Leaps\Database\Eloquent\Relations;
use Leaps\Database\Eloquent\Model;
class Pivot extends Model
{

    /**
     * The parent model of the relationship.
     *
     * @var \Leaps\Database\Eloquent\Model
     */
    protected $parent;

    /**
     * The name of the foreign key column.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the "other key" column.
     *
     * @var string
     */
    protected $otherKey;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = array ();

    /**
     * Create a new pivot model instance.
     *
     * @param \Leaps\Database\Eloquent\Model $parent
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @return void
     */
    public function __construct(Model $parent, $attributes, $table, $exists = false)
    {
        parent::__construct ();
        $this->setRawAttributes ( $attributes );
        $this->setTable ( $table );
        $this->setConnection ( $parent->getConnectionName () );
        $this->parent = $parent;
        $this->exists = $exists;
        $this->timestamps = $this->hasTimestampAttributes ();
    }

    /**
     * Set the keys for a save update query.
     *
     * @param \Leaps\Database\Eloquent\Builder
     * @return \Leaps\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where ( $this->foreignKey, $this->getAttribute ( $this->foreignKey ) );
        return $query->where ( $this->otherKey, $this->getAttribute ( $this->otherKey ) );
    }

    /**
     * Delete the pivot model record from the database.
     *
     * @return int
     */
    public function delete()
    {
        $foreign = $this->getAttribute ( $this->foreignKey );
        $query = $this->newQuery ()->where ( $this->foreignKey, $foreign );
        return $query->where ( $this->otherKey, $this->getAttribute ( $this->otherKey ) )->delete ();
    }

    /**
     * Get the foreign key column name.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the "other key" column name.
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->otherKey;
    }

    /**
     * Set the key names for the pivot model instance.
     *
     * @param string $foreignKey
     * @param string $otherKey
     * @return void
     */
    public function setPivotKeys($foreignKey, $otherKey)
    {
        $this->foreignKey = $foreignKey;
        $this->otherKey = $otherKey;
    }

    /**
     * Determine if the pivot model has timestamp attributes.
     *
     * @return bool
     */
    public function hasTimestampAttributes()
    {
        return array_key_exists ( $this->getCreatedAtColumn (), $this->attributes );
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return $this->parent->getCreatedAtColumn ();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return $this->parent->getUpdatedAtColumn ();
    }
}