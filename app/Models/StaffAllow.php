<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAllow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
    ];

    /**
     * Normalize emails before persistence so uniqueness remains consistent.
     */
    protected function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }
}
