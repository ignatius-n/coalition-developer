<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['uuid', 'name', 'description'])]
#[Hidden(['id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, AddUUIDTrait;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
