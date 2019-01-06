<?php

namespace Tests\Browser;

use D2EM;

use Entities\Switcher as SwitcherEntity;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SwitchControllerTest extends DuskTestCase
{
    public function tearDown()
    {
        foreach( [ 'phpunit', 'phpunit2' ] as $name ) {
            $switch = D2EM::getRepository( SwitcherEntity::class )->findOneBy( [ 'name' => $name ] );

            if( $switch ) {
                D2EM::remove( $switch );
                D2EM::flush();
            }
        }

        parent::tearDown();
    }

    /**
     * A Dusk test example.
     *
     * @return void
     * @throws \Throwable
     */
    public function testAdd()
    {

        $this->browse(function (Browser $browser) {
            $browser->resize( 1600,1200 )
                    ->visit('/auth/login')
                    ->type( 'username', 'travis' )
                    ->type( 'password', 'travisci' )
                    ->press( 'submit' )
                    ->assertPathIs( '/admin' );

            $browser->visit( '/switch/list' )
                ->assertSee( 'switch1' )
                ->assertSee( 'switch2' );

            $browser->visit( '/switch/add-by-snmp' )
                ->assertSee( 'Add Switch via SNMP' )
                ->assertSee( 'Hostname' )
                ->assertSee( 'SNMP Community' );


            // 1. test add by snmp and flow to next step:
            $browser->type( 'hostname', 'phpunit.test.example.com' )
                ->type( 'snmppasswd', 'mXPOSpC52cSFg1qN' )
                ->press('Next ≫' )
                ->assertPathIs('/switch/store-by-snmp' )
                ->assertInputValue( 'name',       'phpunit' )
                ->assertInputValue( 'hostname',   'phpunit.test.example.com' )
                ->assertInputValue( 'snmppasswd', 'mXPOSpC52cSFg1qN' )
                ->assertInputValue( 'model',      'FESX648' )
                ->assertSelected(   'vendorid',   '12')
                ->assertChecked( 'active' );

            // 2. test add step 2
            $browser->select( 'cabinetid', 1 )
                ->select( 'infrastructure', 1 )
                ->type( 'ipv4addr', '192.0.2.1' )
                ->type( 'ipv6addr', '2001:db8::999' )
                ->type( 'mgmt_mac_address', 'AA:00.11:BB.22-87' )
                ->type( 'asn', '65512' )
                ->type( 'loopback_ip', '127.0.0.1' )
                ->type( 'loopback_name', 'lo0' )
                ->type( 'notes', 'Test note' )
                ->press( 'Add' )
                ->assertPathIs('/switch/list' )
                ->assertSee( 'phpunit' )
                ->assertSee( 'FESX648' );

            // get the switch:
            /** @var SwitcherEntity $switch */
            $switch = D2EM::getRepository( SwitcherEntity::class )->findOneBy( [ 'name' => 'phpunit' ] );

            // test the values:
            $this->assertEquals( 'phpunit',                  $switch->getName() );
            $this->assertEquals( 'phpunit.test.example.com', $switch->getHostname() );
            $this->assertEquals( 'mXPOSpC52cSFg1qN',         $switch->getSnmppasswd() );
            $this->assertEquals( 1,                          $switch->getCabinet()->getId() );
            $this->assertEquals( 1,                          $switch->getInfrastructure()->getId() );
            $this->assertEquals( 12,                         $switch->getVendor()->getId() );
            $this->assertEquals( 'FESX648',                  $switch->getModel() );
            $this->assertEquals( true,                       $switch->getActive() );
            $this->assertEquals( '192.0.2.1',                $switch->getIpv4addr() );
            $this->assertEquals( '2001:db8::999',            $switch->getIpv6addr() );
            $this->assertEquals( 'aa0011bb2287',             $switch->getMgmtMacAddress() );
            $this->assertEquals( 65512,                      $switch->getAsn() );
            $this->assertEquals( '127.0.0.1',                $switch->getLoopbackIP() );
            $this->assertEquals( 'lo0',                      $switch->getLoopbackName() );
            $this->assertEquals( 'Test note',                $switch->getNotes() );

            // test that editing while not making any changes and saving changes nothing

            $browser->visit( '/switch/edit/' . $switch->getId() )
                ->assertPathIs('/switch/edit/' . $switch->getId() )
                ->press( 'Save Changes' )
                ->assertPathIs('/switch/list' )
                ->assertSee( 'phpunit' )
                ->assertSee( 'FESX648' );

            // test the values:
            D2EM::refresh($switch);
            $this->assertEquals( 'phpunit',                  $switch->getName() );
            $this->assertEquals( 'phpunit.test.example.com', $switch->getHostname() );
            $this->assertEquals( 'mXPOSpC52cSFg1qN',         $switch->getSnmppasswd() );
            $this->assertEquals( 1,                          $switch->getCabinet()->getId() );
            $this->assertEquals( 1,                          $switch->getInfrastructure()->getId() );
            $this->assertEquals( 12,                         $switch->getVendor()->getId() );
            $this->assertEquals( 'FESX648',                  $switch->getModel() );
            $this->assertEquals( true,                       $switch->getActive() );
            $this->assertEquals( '192.0.2.1',                $switch->getIpv4addr() );
            $this->assertEquals( '2001:db8::999',            $switch->getIpv6addr() );
            $this->assertEquals( 'aa0011bb2287',             $switch->getMgmtMacAddress() );
            $this->assertEquals( 65512,                      $switch->getAsn() );
            $this->assertEquals( '127.0.0.1',                $switch->getLoopbackIP() );
            $this->assertEquals( 'lo0',                      $switch->getLoopbackName() );
            $this->assertEquals( 'Test note',                $switch->getNotes() );


            // now test that editing while making changes works

            $browser->visit( '/switch/edit/' . $switch->getId() )
                ->assertPathIs('/switch/edit/' . $switch->getId() )
                ->type( 'name', 'phpunit2' )
                ->type( 'hostname', 'phpunit2.test.example.com' )
                ->type( 'snmppasswd', 'newpassword' )
                ->select( 'infrastructure', 2 )
                ->select( 'vendorid', 11 )
                ->type( 'model', 'TI24X' )
                ->uncheck( 'active' )
                ->type( 'ipv4addr', '192.0.2.2' )
                ->type( 'ipv6addr', '2001:db8::9999' )
                ->type( 'mgmt_mac_address', 'AA:00.11:BB.22-88' )
                ->type( 'asn', '65513' )
                ->type( 'loopback_ip', '127.0.0.2' )
                ->type( 'loopback_name', 'lo1' )
                ->type( 'notes', 'Test note 2' )
                ->press( 'Save Changes' )
                ->assertPathIs('/switch/list' )
                ->assertSee( 'phpunit2' )
                ->assertSee( 'TI24X' );

            D2EM::refresh($switch);

            // test the values:
            $this->assertEquals( 'phpunit2',                  $switch->getName() );
            $this->assertEquals( 'phpunit2.test.example.com', $switch->getHostname() );
            $this->assertEquals( 'newpassword',               $switch->getSnmppasswd() );
            $this->assertEquals( 1,                           $switch->getCabinet()->getId() );
            $this->assertEquals( 2,                           $switch->getInfrastructure()->getId() );
            $this->assertEquals( 11,                          $switch->getVendor()->getId() );
            $this->assertEquals( 'TI24X',                     $switch->getModel() );
            $this->assertEquals( false,                       $switch->getActive() );
            $this->assertEquals( '192.0.2.2',                 $switch->getIpv4addr() );
            $this->assertEquals( '2001:db8::9999',            $switch->getIpv6addr() );
            $this->assertEquals( 'aa0011bb2288',              $switch->getMgmtMacAddress() );
            $this->assertEquals( 65513,                       $switch->getAsn() );
            $this->assertEquals( '127.0.0.2',                 $switch->getLoopbackIP() );
            $this->assertEquals( 'lo1',                       $switch->getLoopbackName() );
            $this->assertEquals( 'Test note 2',               $switch->getNotes() );

            // test that editing while not making any changes and saving changes nothing
            // (this is a retest for, e.g. unchecked checkboxes)

            $browser->visit( '/switch/edit/' . $switch->getId() )
                ->assertPathIs('/switch/edit/' . $switch->getId() )
                ->press( 'Save Changes' )
                ->assertPathIs('/switch/list' )
                ->assertSee( 'phpunit2' )
                ->assertSee( 'TI24X' );

            // test the values:
            $this->assertEquals( 'phpunit2',                  $switch->getName() );
            $this->assertEquals( 'phpunit2.test.example.com', $switch->getHostname() );
            $this->assertEquals( 'newpassword',               $switch->getSnmppasswd() );
            $this->assertEquals( 1,                           $switch->getCabinet()->getId() );
            $this->assertEquals( 2,                           $switch->getInfrastructure()->getId() );
            $this->assertEquals( 11,                          $switch->getVendor()->getId() );
            $this->assertEquals( 'TI24X',                     $switch->getModel() );
            $this->assertEquals( false,                       $switch->getActive() );
            $this->assertEquals( '192.0.2.2',                 $switch->getIpv4addr() );
            $this->assertEquals( '2001:db8::9999',            $switch->getIpv6addr() );
            $this->assertEquals( 'aa0011bb2288',              $switch->getMgmtMacAddress() );
            $this->assertEquals( 65513,                       $switch->getAsn() );
            $this->assertEquals( '127.0.0.2',                 $switch->getLoopbackIP() );
            $this->assertEquals( 'lo1',                       $switch->getLoopbackName() );
            $this->assertEquals( 'Test note 2',               $switch->getNotes() );

            // delete this switch
            $browser->press( '#d2f-list-delete-' . $switch->getId() )
                ->waitForText( 'Do you really want to delete this' )
                ->press( 'Delete' )
                ->assertPathIs('/switch/list' )
                ->assertDontSee( 'phpunit2' )
                ->assertDontSee( 'TI24X' );


        });

    }
}