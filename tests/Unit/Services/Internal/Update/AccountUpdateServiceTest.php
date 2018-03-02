<?php
/**
 * AccountUpdateServiceTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Services\Internal\Update;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use Tests\TestCase;

/**
 * Class AccountUpdateServiceTest
 */
class AccountUpdateServiceTest extends TestCase
{
    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testDeleteExistingIB()
    {
        /** @var Account $account */
        $account  = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . rand(1, 1000),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        $opposing = $this->user()->accounts()->first();
        $journal  = TransactionJournal::create(
            ['user_id' => $this->user()->id, 'transaction_type_id' => 4, 'transaction_currency_id' => 1, 'description' => 'IB',
             'date'    => '2018-01-01', 'completed' => true,
            ]
        );
        // transactions:
        Transaction::create(
            ['account_id'              => $account->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => '100', 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $opposing->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => '-100', 'identifier' => 0,]
        );


        $data = [
            'name'           => 'Some new name #' . rand(1, 1000),
            'active'         => true,
            'virtualBalance' => '0',
            'iban'           => null,
            'accountRole'    => 'defaultAsset',
            'notes'          => 'Hello',
            'currency_id'    => 1,
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        $this->assertEquals(0, $account->transactions()->count());
        /** @var Note $note */
        $note = $account->notes()->first();
        $this->assertEquals($data['notes'], $note->text);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateBasic()
    {
        /** @var Account $account */
        $account = $this->user()->accounts()->first();
        $data    = [
            'name'           => 'Some new name #' . rand(1, 1000),
            'active'         => true,
            'virtualBalance' => '0',
            'iban'           => null,
            'accountRole'    => 'defaultAsset',
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateExistingIB()
    {
        /** @var Account $account */
        $account  = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . rand(1, 1000),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        $opposing = $this->user()->accounts()->first();
        $journal  = TransactionJournal::create(
            ['user_id' => $this->user()->id, 'transaction_type_id' => 4, 'transaction_currency_id' => 1, 'description' => 'IB',
             'date'    => '2018-01-01', 'completed' => true,
            ]
        );
        // transactions:
        Transaction::create(
            ['account_id'              => $account->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => '100', 'identifier' => 0,]
        );
        Transaction::create(
            ['account_id'              => $opposing->id, 'transaction_journal_id' => $journal->id,
             'transaction_currency_id' => 1, 'amount' => '-100', 'identifier' => 0,]
        );


        $data = [
            'name'               => 'Some new name #' . rand(1, 1000),
            'active'             => true,
            'virtualBalance'     => '0',
            'iban'               => null,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => '105',
            'openingBalanceDate' => new Carbon('2018-01-01'),
            'notes'              => 'Hello',
            'currency_id'        => 1,
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(105, $account->transactions()->first()->amount);
        /** @var Note $note */
        $note = $account->notes()->first();
        $this->assertEquals($data['notes'], $note->text);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\AccountUpdateService
     * @covers \FireflyIII\Services\Internal\Support\AccountServiceTrait
     */
    public function testUpdateNewIB()
    {
        /** @var Account $account */
        $account = Account::create(
            ['user_id'         => $this->user()->id, 'account_type_id' => 1, 'name' => 'Some name #' . rand(1, 1000),
             'virtual_balance' => '0', 'iban' => null, 'active' => true]
        );
        $data    = [
            'name'               => 'Some new name #' . rand(1, 1000),
            'active'             => true,
            'virtualBalance'     => '0',
            'iban'               => null,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => '100',
            'openingBalanceDate' => new Carbon('2018-01-01'),
            'notes'              => 'Hello',
            'currency_id'        => 1,
        ];

        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);
        $account = $service->update($account, $data);

        $this->assertEquals($data['name'], $account->name);
        $this->assertEquals(1, $account->transactions()->count());
        $this->assertEquals(100, $account->transactions()->first()->amount);
        /** @var Note $note */
        $note = $account->notes()->first();
        $this->assertEquals($data['notes'], $note->text);
    }

}