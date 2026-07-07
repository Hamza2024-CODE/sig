<?php
 
 namespace App\Listeners;
 
 use App\Events\SecurityEventTriggered;
 use App\Models\SecurityLog;
 
 class LogSecurityActivity
 {
     /**
      * Handle the event.
      */
     public function handle(SecurityEventTriggered $event): void
     {
         $userId = null;
         $userData = [];

         if ($event->user) {
             if ($event->user instanceof \App\Models\User) {
                 $userId = $event->user->IDUtilisateur;
                 $userData = [
                     'id' => $event->user->IDUtilisateur,
                     'username' => $event->user->NomUser,
                     'name' => $event->user->Nom,
                     'table' => 'utilisateur',
                     'role' => $event->user->IDNature == 1 ? 'مدير النظام / إدارة عليا' : ($event->user->IDNature == 2 ? 'مديرية مركزية' : 'مستعمل المنصة'),
                     'mfa' => $event->user->mfa_enabled ? 'مفعل' : 'غير مفعل',
                 ];
             } elseif ($event->user instanceof \App\Models\Etablissement) {
                 $userId = $event->user->IDetablissement;
                 $userData = [
                     'id' => $event->user->IDetablissement,
                     'username' => $event->user->nomUser,
                     'name' => $event->user->Nom,
                     'table' => 'etablissement',
                     'role' => 'مؤسسة تكوين مهني / مركز / معهد',
                     'mfa' => $event->user->mfa_enabled ? 'مفعل' : 'غير مفعل',
                 ];
             } elseif ($event->user instanceof \App\Models\Encadrement) {
                 $userId = $event->user->IDEncadrement;
                 $userData = [
                     'id' => $event->user->IDEncadrement,
                     'username' => $event->user->nin,
                     'name' => ($event->user->Nom ?? '') . ' ' . ($event->user->Prenom ?? ''),
                     'table' => 'encadrement',
                     'role' => 'موظف / مؤطر / أستاذ',
                     'mfa' => $event->user->mfa_enabled ? 'مفعل' : 'غير مفعل',
                 ];
             } else {
                 $userId = $event->user->IDUtilisateur ?? $event->user->IDetablissement ?? $event->user->IDEncadrement ?? null;
             }
         }

         $metadata = is_array($event->metadata) ? $event->metadata : [];
         if (!empty($userData)) {
             $metadata['user_details'] = $userData;
         }

         SecurityLog::create([
             'user_id'     => $userId,
             'event_type'  => $event->eventType,
             'severity'    => $event->severity,
             'description' => $event->description,
             'ip_address'  => $event->ipAddress,
             'user_agent'  => $event->userAgent,
             'metadata'    => empty($metadata) ? null : $metadata,
         ]);
     }
 }
