<?php

namespace Modules\GlobalAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

// use Modules\GlobalAdmin\Database\Factories\DomainFactory;

class Domain extends Model
{
    protected $table = 'domains';

    // UUID comme clé primaire
    public $incrementing = false;
    protected $keyType = 'string';

    // Champs fillable
    protected $fillable = [
        'id',
        'tenant_id',
        'domain',
    ];

    /**
     * Boot method pour générer automatiquement UUID
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Générer UUID si pas défini
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation vers Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(\Modules\GlobalAdmin\Models\Tenant::class);
    }
}
