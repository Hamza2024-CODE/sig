<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Apprenant — Eloquent Model
 *
 * قواعد الأداء الإلزامية:
 * ✅ استخدم paginate(20) أو cursorPaginate() دائماً — لا ::all()
 * ✅ استخدم with([...]) قبل paginate() لتفادي N+1
 * ✅ اختر الأعمدة المطلوبة فقط بـ select([...])
 *
 * مثال صحيح:
 *   Apprenant::with(['candidat:IDCandidat,Nom,Prenom', 'section.offre.specialite:IDSpecialite,Nom'])
 *             ->select(['IDapprenant','IDCandidat','IDSection','Nccp'])
 *             ->paginate(20);
 *
 * مثال خاطئ ❌:
 *   Apprenant::all();        // يجلب آلاف السجلات دفعة واحدة
 *   Apprenant::with(['candidat'])->get(); // N+1 بدون select
 */
class Apprenant extends Model
{
    protected $table      = 'apprenant';
    protected $primaryKey = 'IDapprenant';
    public    $timestamps = false;

    protected $fillable = [
        'IDCandidat',
        'IDSection',
        'Nccp',
        'DateInscr',
        'NbrAbsences',
        'mode_formation',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function candidat()
    {
        return $this->belongsTo(Candidat::class, 'IDCandidat', 'IDCandidat');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'IDSection', 'IDSection');
    }

    public function semestres()
    {
        return $this->hasMany(ApprenantSectionSemestre::class, 'IDapprenant', 'IDapprenant');
    }

    // ── Scopes (استعمل هذه بدلاً من كتابة WHERE يدوياً) ─────────────────────

    /**
     * ✅ النطاق الأساسي لعرض قوائم المتربصين (مع Eager Loading محسوب)
     * الاستخدام: Apprenant::forListing()->paginate(20);
     */
    public function scopeForListing(Builder $query): Builder
    {
        return $query
            ->select(['IDapprenant', 'IDCandidat', 'IDSection', 'Nccp', 'NumActe', 'statut', 'Groupe'])
            ->with([
                'candidat:IDCandidat,Nom,Prenom,NomFr,PrenomFr,Civ,Nin,dateInscr',
                'section:IDSection,IDOffre,Nom',
                'section.offre:IDOffre,IDSpecialite,IDEts_Form,IDMode_formation',
                'section.offre.specialite:IDSpecialite,Nom,CodeSpec',
                'section.offre.etablissement:IDetablissement,Nom,Code',
            ]);
    }

    /**
     * ✅ نطاق بالولاية — لمدير ولائي (DFEP)
     * Apprenant::forDfep($dfepId)->paginate(20);
     */
    public function scopeForDfep(Builder $query, int $dfepId): Builder
    {
        return $query->whereHas('section.offre.etablissement', function (Builder $q) use ($dfepId) {
            $q->where('IDDFEP', $dfepId);
        });
    }

    /**
     * ✅ نطاق بالمؤسسة — لمدير المؤسسة (Directeur)
     * Apprenant::forEtab($etabId)->paginate(20);
     */
    public function scopeForEtab(Builder $query, int $etabId): Builder
    {
        return $query->whereHas('section.offre', function (Builder $q) use ($etabId) {
            $q->where('IDEts_Form', $etabId);
        });
    }

    /**
     * ✅ نطاق البحث النصي (Nom / Prenom / Nccp)
     * Apprenant::searchQuery('أحمد')->paginate(20);
     */
    public function scopeSearchQuery(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('Nccp', 'LIKE', "%{$term}%")
              ->orWhereHas('candidat', function (Builder $sq) use ($term) {
                  $sq->where('Nom', 'LIKE', "%{$term}%")
                     ->orWhere('Prenom', 'LIKE', "%{$term}%")
                     ->orWhere('NomFr', 'LIKE', "%{$term}%");
              });
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNomCompletAttribute(): string
    {
        return trim(($this->candidat?->Nom ?? '') . ' ' . ($this->candidat?->Prenom ?? ''));
    }
}
