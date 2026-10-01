<?php

namespace App\Support;

use App\Models\AttendanceSheet;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;

/**
 * Éléments en attente d'action pour l'utilisateur (cloche de la barre haute
 * et bloc « À traiter » du tableau de bord). Mis en cache 60 s.
 */
final class Approvals
{
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return Cache::remember("approvals:{$user->id}:".company_id(), 60, function () use ($user) {
            $cid = company_id();
            $items = [];
            $can = fn (string $permission) => $user->isMaster() || $user->can($permission);

            if ($can('vouchers.approve')) {
                $items[] = ['module' => 'vouchers', 'label' => 'Bons à approuver', 'icon' => 'ticket', 'count' => Voucher::where('company_id', $cid)->where('status', 'submitted')->count(), 'url' => route('vouchers', ['status' => 'submitted'])];
            }
            if ($can('vouchers.execute')) {
                $items[] = ['module' => 'vouchers', 'label' => 'Bons approuvés à exécuter', 'icon' => 'check-badge', 'count' => Voucher::where('company_id', $cid)->where('status', 'approved')->count(), 'url' => route('vouchers', ['status' => 'approved'])];
            }
            if ($can('leaves.approve')) {
                $items[] = ['module' => 'leaves', 'label' => 'Demandes de congé', 'icon' => 'sun', 'count' => LeaveRequest::where('company_id', $cid)->where('status', 'pending')->count(), 'url' => route('leaves')];
            }
            if ($can('expense-claims.approve')) {
                $items[] = ['module' => 'expense-claims', 'label' => 'Notes de frais soumises', 'icon' => 'receipt-percent', 'count' => ExpenseClaim::where('company_id', $cid)->where('status', 'submitted')->count(), 'url' => route('expense-claims')];
            }
            if ($can('attendance.manage')) {
                $items[] = ['module' => 'attendance', 'label' => 'Présences à valider', 'icon' => 'clipboard-document-check', 'count' => AttendanceSheet::where('company_id', $cid)->where('status', 'draft')->whereDate('work_date', '<', today())->count(), 'url' => route('attendance')];
            }
            if ($can('orders.validate')) {
                $items[] = ['module' => 'orders', 'label' => 'Devis en attente de validation', 'icon' => 'document-check', 'count' => SalesOrder::where('company_id', $cid)->where('stage', 'devis')->count(), 'url' => route('orders.index', ['stage' => 'devis'])];
            }
            if ($can('cash.view')) {
                $items[] = ['module' => 'cash', 'label' => 'Commandes facturées à encaisser', 'icon' => 'banknotes', 'count' => SalesOrder::where('company_id', $cid)->where('stage', 'facture')->count(), 'url' => route('cash')];
            }

            return array_values(array_filter($items, fn ($i) => $i['count'] > 0));
        });
    }

    public static function forget(): void
    {
        foreach (User::pluck('id') as $id) {
            Cache::forget("approvals:{$id}:".company_id());
        }
    }
}
