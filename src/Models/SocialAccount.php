<?php

declare(strict_types=1);

namespace Fureev\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SocialAccount
 *
 * @package App\Models
 * @property int $id
 * @property int $user_id
 * @property string $provider_user_id
 * @property string $provider
 * @property array $raw
 * @method static SocialAccount|\Illuminate\Database\Eloquent\Builder whereProvider(string $provider)
 * @method SocialAccount|\Illuminate\Database\Eloquent\Builder whereProviderUserId (string $userId)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class SocialAccount extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'provider_user_id',
        'provider',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('social.userClass', 'App/User'));
    }
}
