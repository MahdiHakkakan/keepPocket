<?php

use App\Models\Attachment;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\RecurringRule;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;

it('connects ledger records through their schema relationships', function () {
    $user = User::factory()->create();
    $ledger = $user->ledgers()->create([
        'name' => 'Personal',
        'description' => 'Household finances',
    ]);
    $parentAccount = $ledger->accounts()->create([
        'code' => '1000',
        'name' => 'Assets',
        'type' => 1,
    ]);
    $account = $parentAccount->children()->create([
        'ledger_id' => $ledger->id,
        'code' => '1010',
        'name' => 'Cash',
        'type' => 1,
    ]);
    $parentCategory = $ledger->categories()->create([
        'name' => 'Living',
        'type' => 'expense',
    ]);
    $category = $parentCategory->children()->create([
        'ledger_id' => $ledger->id,
        'name' => 'Groceries',
        'type' => 'expense',
    ]);
    $transaction = $ledger->transactions()->create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'transaction_date' => '2026-09-11',
        'description' => 'Weekly shop',
    ]);
    $journalEntry = $transaction->journalEntries()->create([
        'ledger_id' => $ledger->id,
        'entry_date' => '2026-09-11',
        'created_by' => $user->id,
    ]);
    $journalLine = $journalEntry->lines()->create([
        'account_id' => $account->id,
        'debit' => 12500,
    ]);
    $attachment = $transaction->attachments()->create([
        'path' => 'receipts/shop.pdf',
        'original_name' => 'shop.pdf',
        'mime_type' => 'application/pdf',
        'size' => 2048,
    ]);
    $budget = $category->budgets()->create([
        'ledger_id' => $ledger->id,
        'amount' => 500000,
        'type' => 'monthly',
        'year' => 2026,
        'month' => 9,
    ]);
    $goal = $ledger->goals()->create([
        'name' => 'Emergency fund',
        'target_amount' => 1000000,
        'target_date' => '2027-09-11',
    ]);
    $recurringRule = $account->recurringRules()->create([
        'ledger_id' => $ledger->id,
        'category_id' => $category->id,
        'amount' => 12500,
        'frequency' => 'weekly',
        'start_date' => '2026-09-11',
    ]);

    expect($ledger->user->is($user))->toBeTrue()
        ->and($account->parent->is($parentAccount))->toBeTrue()
        ->and($parentAccount->children->first()->is($account))->toBeTrue()
        ->and($category->parent->is($parentCategory))->toBeTrue()
        ->and($transaction->account->is($account))->toBeTrue()
        ->and($transaction->category->is($category))->toBeTrue()
        ->and($journalEntry->creator->is($user))->toBeTrue()
        ->and($journalLine->journalEntry->is($journalEntry))->toBeTrue()
        ->and($attachment)->toBeInstanceOf(Attachment::class)
        ->and($budget)->toBeInstanceOf(Budget::class)
        ->and($goal)->toBeInstanceOf(Goal::class)
        ->and($recurringRule)->toBeInstanceOf(RecurringRule::class)
        ->and($ledger->refresh()->is_active)->toBeTrue()
        ->and($transaction->transaction_date->toDateString())->toBe('2026-09-11')
        ->and($journalLine->debit)->toBe(12500)
        ->and($attachment->size)->toBe(2048);

    expect($ledger->transactions->first())->toBeInstanceOf(Transaction::class)
        ->and($ledger->journalEntries->first())->toBeInstanceOf(JournalEntry::class)
        ->and($account->journalLines->first())->toBeInstanceOf(JournalLine::class)
        ->and($ledger->budgets->first())->toBeInstanceOf(Budget::class)
        ->and($ledger->goals->first())->toBeInstanceOf(Goal::class)
        ->and($ledger->recurringRules->first())->toBeInstanceOf(RecurringRule::class);
});

it('attaches tags to transactions through the polymorphic pivot', function () {
    $user = User::factory()->create();
    $ledger = $user->ledgers()->create(['name' => 'Personal']);
    $account = $ledger->accounts()->create([
        'code' => '1000',
        'name' => 'Cash',
        'type' => 1,
    ]);
    $transaction = $ledger->transactions()->create([
        'account_id' => $account->id,
        'transaction_date' => '2026-09-11',
    ]);
    $tag = $ledger->tags()->create(['name' => 'Essential']);

    $transaction->tags()->attach($tag);

    expect($transaction->tags()->first()->is($tag))->toBeTrue()
        ->and($tag->transactions()->first()->is($transaction))->toBeTrue()
        ->and($tag->taggables)->toHaveCount(1)
        ->and($tag->taggables->first()->taggable->is($transaction))->toBeTrue()
        ->and($ledger->tags->first())->toBeInstanceOf(Tag::class);
});
