<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EntrepreneurProfile;
use App\Models\SocialOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialLinkageApiController extends Controller
{
    /**
     * CU2: Listado de organizaciones aliadas y convenios sociales.
     */
    public function indexOrganizations(): JsonResponse
    {
        $organizations = SocialOrganization::withCount('entrepreneurProfiles')
            ->orderBy('name')
            ->get();

        return response()->json([
            'ok' => true,
            'organizations' => $organizations,
        ]);
    }

    public function storeOrganization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'organization_type' => ['required', 'string', 'in:asociacion,fundacion,municipio,comunidad,independiente'],
            'municipality' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:80'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'agreement_status' => ['required', 'string', 'in:activo,en_gestion,finalizado'],
            'beneficiaries_target' => ['required', 'integer', 'min:1', 'max:50000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $org = SocialOrganization::create(array_merge(
            ['department' => 'Santa Cruz'],
            $validated
        ));

        AuditLog::record('create', 'social_organizations', "Nuevo convenio/organización social: {$org->name}");

        return response()->json([
            'ok' => true,
            'organization' => $org,
            'message' => 'Organización de vinculación social registrada correctamente.',
        ], 201);
    }

    public function updateOrganization(Request $request, SocialOrganization $organization): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'organization_type' => ['required', 'string', 'in:asociacion,fundacion,municipio,comunidad,independiente'],
            'municipality' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:80'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'agreement_status' => ['required', 'string', 'in:activo,en_gestion,finalizado'],
            'beneficiaries_target' => ['required', 'integer', 'min:1', 'max:50000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $organization->update($validated);
        AuditLog::record('update', 'social_organizations', "Actualización de convenio social: {$organization->name}");

        return response()->json([
            'ok' => true,
            'organization' => $organization,
            'message' => 'Datos de la organización actualizados.',
        ]);
    }

    public function destroyOrganization(SocialOrganization $organization): JsonResponse
    {
        $name = $organization->name;
        $organization->delete();
        AuditLog::record('delete', 'social_organizations', "Eliminación de convenio social: {$name}");

        return response()->json([
            'ok' => true,
            'message' => "La organización «{$name}» fue eliminada.",
        ]);
    }

    /**
     * Ficha propia del participante / microempresario (CU2).
     */
    public function myProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EntrepreneurProfile::with(['socialOrganization', 'verifier:id,name'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'ok' => true,
            'profile' => $profile,
            'organizations' => SocialOrganization::where('agreement_status', 'activo')
                ->orderBy('name')
                ->get(['id', 'name', 'municipality', 'organization_type']),
        ]);
    }

    public function saveMyProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'social_organization_id' => ['nullable', 'integer', 'exists:social_organizations,id'],
            'business_name' => ['required', 'string', 'min:2', 'max:150'],
            'sector' => ['required', 'string', 'max:80'],
            'business_stage' => ['required', 'string', 'in:idea,inicial,en_marcha,consolidacion'],
            'employees_count' => ['required', 'integer', 'min:0', 'max:500'],
            'monthly_income_range' => ['nullable', 'string', 'max:60'],
            'municipality' => ['required', 'string', 'max:100'],
            'vulnerability_group' => ['nullable', 'string', 'max:80'],
            'social_impact_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = EntrepreneurProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        AuditLog::record('update', 'entrepreneur_profiles', "Ficha de emprendimiento guardada: {$profile->business_name}");

        return response()->json([
            'ok' => true,
            'profile' => $profile->load(['socialOrganization', 'verifier:id,name']),
            'message' => '¡Tu ficha de emprendimiento y vinculación social fue guardada con éxito!',
        ]);
    }

    /**
     * Panel administrativo/ejecutivo de fichas de emprendimiento e impacto social (CU2).
     */
    public function indexProfiles(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $sector = trim((string) $request->input('sector', ''));

        $query = EntrepreneurProfile::with([
            'user:id,name,email,phone,city,gender,birth_date',
            'socialOrganization:id,name,municipality',
            'verifier:id,name',
        ]);

        if ($search !== '') {
            $query->where(function ($b) use ($search) {
                $b->where('business_name', 'ilike', "%{$search}%")
                  ->orWhere('municipality', 'ilike', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($sector !== '') {
            $query->where('sector', $sector);
        }

        return response()->json([
            'ok' => true,
            'profiles' => $query->orderByDesc('updated_at')->paginate(10)->withQueryString(),
            'organizations' => SocialOrganization::withCount('entrepreneurProfiles')->orderBy('name')->get(),
        ]);
    }

    public function verifyProfile(Request $request, EntrepreneurProfile $profile): JsonResponse
    {
        $validated = $request->validate([
            'social_impact_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile->update([
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'social_impact_notes' => $validated['social_impact_notes'] ?? $profile->social_impact_notes,
        ]);

        AuditLog::record('update', 'entrepreneur_profiles', "Verificación oficial de emprendimiento #{$profile->id}");

        return response()->json([
            'ok' => true,
            'profile' => $profile->load(['user:id,name,email', 'socialOrganization', 'verifier:id,name']),
            'message' => 'Ficha de vinculación social verificada oficialmente.',
        ]);
    }
}
