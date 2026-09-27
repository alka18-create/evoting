<?php

namespace App\Domain\Auditing\Services;

use App\Models\AuditLog;

class AuditLogger
{
    /**
     * Catat event audit.
     *
     * @param  string  $action  Contoh: LOGIN, ELECTION_OPENED, VOTE_CAST
     * @param  string|null  $resourceType  Contoh: Election, Voter
     * @param  int|string|null  $resourceId
     * @param  array  $metadata  Data tambahan (jangan simpan voter→candidate)
     */
    /**
     * P1-05: untuk aksi ballot (VOTE_CAST*) jangan simpan IP/UA mentah agar
     * timing ballot tidak mudah dikorelasikan dengan identitas (Threat Model §13).
     * Audit admin tetap menyimpan IP/UA (truncated) untuk incident response.
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        int|string|null $resourceId = null,
        array $metadata = [],
        bool $storeNetwork = true,
    ): AuditLog {
        // Guard: jangan pernah log token/plain credential atau pemetaan voter→candidate.
        unset($metadata['token'], $metadata['plain_token'], $metadata['credential'], $metadata['candidate_id']);
        foreach ($metadata as $k => $v) {
            if (is_string($v) && strlen($v) > 2000) {
                $metadata[$k] = substr($v, 0, 2000);
            }
        }

        $request = request();
        $user = $request->user();

        $isBallotAction = str_starts_with($action, 'VOTE_CAST');
        $storeNetwork = $storeNetwork && ! $isBallotAction;

        $userAgent = $request->userAgent();
        if (is_string($userAgent) && strlen($userAgent) > 255) {
            $userAgent = substr($userAgent, 0, 255);
        }

        return AuditLog::create([
            'actor_user_id' => $user instanceof \App\Models\User ? $user->id : null,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'ip_address' => $storeNetwork ? $request->ip() : null,
            'user_agent' => $storeNetwork ? $userAgent : null,
            'created_at' => now(),
        ]);
    }
}