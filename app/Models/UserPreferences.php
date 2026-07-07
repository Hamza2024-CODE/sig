<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UserPreferences — Per-user settings stored in the database.
 * Maps to the user_preferences table (created via migration).
 *
 * user_id maps to:
 *   - utilisateur.IDUtilisateur    (user_type = 'utilisateur')
 *   - Etablissement.IDetablissement (user_type = 'etablissement')
 *   - Encadrement.IDEncadrement    (user_type = 'encadrement')
 */
class UserPreferences extends Model
{
    protected $table = 'user_preferences';

    protected $fillable = [
        'user_id', 'user_type', 'username',
        // Theme
        'theme', 'theme_color', 'sidebar_bg', 'accent_color',
        'compact_mode', 'animations_enabled', 'font_size', 'language',
        // Institution branding
        'institution_logo_url', 'institution_code',
        'institution_name_ar', 'institution_name_fr', 'institution_type',
        // Dashboard
        'pinned_widgets', 'hidden_widgets', 'default_tab',
        'items_per_page', 'show_welcome_banner',
        // Notifications
        'notif_email', 'notif_browser', 'notif_sound',
    ];

    protected $casts = [
        'compact_mode'        => 'boolean',
        'animations_enabled'  => 'boolean',
        'show_welcome_banner' => 'boolean',
        'notif_email'         => 'boolean',
        'notif_browser'       => 'boolean',
        'notif_sound'         => 'boolean',
        'pinned_widgets'      => 'array',
        'hidden_widgets'      => 'array',
        'items_per_page'      => 'integer',
    ];

    /**
     * Retrieve or create preferences for a user session.
     *
     * @param array $user The user array from session()
     */
    public static function forUser(array $user): self
    {
        $userId   = (int)($user['id'] ?? 0);
        $userType = match ($user['login_table'] ?? 'utilisateur') {
            'etablissement' => 'etablissement',
            'encadrement'   => 'encadrement',
            default         => 'utilisateur',
        };

        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'user_type'    => $userType,
                'username'     => $user['username'] ?? '',
                'theme'        => 'light',
                'language'     => 'ar',
                'items_per_page' => 25,
            ]
        );
    }

    /**
     * Get default preferences as array (used when no DB record exists).
     */
    public static function defaults(): array
    {
        return [
            'theme'               => 'light',
            'theme_color'         => '#1a6bcc',
            'accent_color'        => '#1a6bcc',
            'compact_mode'        => false,
            'animations_enabled'  => true,
            'font_size'           => 'md',
            'language'            => 'ar',
            'items_per_page'      => 25,
            'show_welcome_banner' => true,
            'notif_browser'       => true,
            'notif_sound'         => true,
        ];
    }
}
