<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use NumberFormatter;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_secure_encode64()
    {
        // Arrange
        $data = 'test_string';
        Config::set('app.key', 'test_app_key');

        // Act
        $result = secureEncode64($data);

        // Assert
        $this->assertEquals(base64_encode($data . env('APP_KEY')), $result);
    }

    public function test_format_currency()
    {
        // Arrange
        $value = 1234.56;

        // Act
        $result = format_currency($value);

        // Assert
        $fmt = new NumberFormatter('fr_FR', NumberFormatter::CURRENCY);
        $this->assertEquals($fmt->formatCurrency($value, 'EUR'), $result);
    }

    public function test_format_number()
    {
        // Arrange
        $value = 1234.56;

        // Act
        $result = format_number($value);

        // Assert
        $fmt = new NumberFormatter('fr_FR', NumberFormatter::DECIMAL_ALWAYS_SHOWN);
        $fmt->setPattern('#,##0.00');
        $this->assertEquals($fmt->format($value), $result);
    }

    public function test_format_date()
    {
        // Arrange
        $date = '2023-04-15';

        // Act
        $result = format_date($date);

        // Assert
        $expected = Carbon::parse($date)->locale('fr_FR')->isoFormat('DD/MM/YYYY');
        $this->assertEquals($expected, $result);
    }

    public function test_format_date_with_null_returns_null()
    {
        $this->assertNull(format_date(null));
    }

    public function test_format_hour()
    {
        // Arrange & Act & Assert
        $this->assertEquals('14:30', format_hour('14:30:00'));
        $this->assertEquals('14:30', format_hour('14:30'));
        $this->assertNull(format_hour(null));
    }

    public function test_format_telephone()
    {
        // Arrange
        $telephone = '0123456789';

        // Act
        $result = format_telephone($telephone);

        // Assert
        $this->assertEquals('01 23 45 67 89', $result);
        $this->assertNull(format_telephone(null));
    }

    public function test_format_siret()
    {
        // Arrange
        $siret = '12345678901234';

        // Act
        $result = format_siret($siret);

        // Assert
        $this->assertEquals('123 456 789 01234', $result);
        $this->assertNull(format_siret(null));
    }

    public function test_format_date_fr_to_eng()
    {
        // Arrange
        $dateFr = '15/04/2023';

        // Act
        $result = format_date_FrToEng($dateFr);

        // Assert
        $this->assertEquals('2023-04-15', $result);
        $this->assertNull(format_date_FrToEng(null));
    }

    public function test_nb_days_between()
    {
        // Arrange
        $startDate = '2023-04-15';
        $endDate = '2023-04-20';

        // Act & Assert
        $this->assertEquals(5, nbDaysBetween($startDate, $endDate));
        $this->assertEquals(6, nbDaysBetween($startDate, $endDate, true));
    }

    public function test_nb_days_off_between()
    {
        // Arrange
        // 15th April 2023 is a Saturday and 16th April 2023 is a Sunday
        $startDate = '2023-04-14';
        $endDate = '2023-04-17';

        // Act
        $result = nbDaysOffBetween($startDate, $endDate);

        // Assert
        $this->assertEquals(2, $result); // 2 weekend days
    }

    public function test_size_file_readable()
    {
        // Arrange & Act & Assert
        $this->assertEquals('0 B', sizeFileReadable(0));
        $this->assertEquals('10 B', sizeFileReadable(10));
        $this->assertEquals('1 kB', sizeFileReadable(1024));
        $this->assertEquals('1 MB', sizeFileReadable(1048576));
        $this->assertEquals('1 GB', sizeFileReadable(1073741824));
    }

    public function test_sanitize_float()
    {
        // Arrange & Act & Assert
        $this->assertEquals(1234.56, sanitizeFloat('1 234,56'));
        $this->assertEquals(1234.56, sanitizeFloat('1,234.56'));
        $this->assertEquals(1234.0, sanitizeFloat('1234'));
        $this->assertEquals(0.0, sanitizeFloat(null));
    }

    public function test_supprimer_decoration()
    {
        // Arrange & Act & Assert
        $this->assertEquals(1234.56, supprimer_decoration('1 234,56'));
        $this->assertEquals(1234.56, supprimer_decoration('1,234.56'));
        $this->assertEquals(1234, supprimer_decoration('1234'));
        $this->assertEquals(0, supprimer_decoration(null));
    }

    public function test_bool_val()
    {
        // Arrange & Act & Assert
        $this->assertTrue(bool_val('1'));
        $this->assertTrue(bool_val('true'));
        $this->assertTrue(bool_val('on'));
        $this->assertTrue(bool_val('yes'));
        $this->assertTrue(bool_val('y'));
        $this->assertTrue(bool_val(true));
        $this->assertTrue(bool_val(1));

        $this->assertFalse(bool_val('0'));
        $this->assertFalse(bool_val('false'));
        $this->assertFalse(bool_val('off'));
        $this->assertFalse(bool_val('no'));
        $this->assertFalse(bool_val('n'));
        $this->assertFalse(bool_val(false));
        $this->assertFalse(bool_val(0));
    }

    public function test_salaries_as_admin()
    {
        // Arrange
        $admin = User::factory()->create();
        $salarie1 = User::factory()->create();
        $salarie2 = User::factory()->create();

        Bouncer::assign('admin')->to($admin);
        Bouncer::assign('salarie')->to($salarie1);
        Bouncer::assign('salarie')->to($salarie2);

        Auth::login($admin);

        // Act
        $result = salaries();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($salarie1));
        $this->assertTrue($result->contains($salarie2));
    }

    public function test_salaries_as_non_admin()
    {
        // Arrange
        $user = User::factory()->create();
        $salarie = User::factory()->create();

        Bouncer::assign('salarie')->to($salarie);

        Auth::login($user);

        // Act
        $result = salaries();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_breadcrumb_item()
    {
        // Arrange & Act
        $item1 = new BreadcrumbItem('Home');
        $item2 = new BreadcrumbItem('Products', '/products');
        $item3 = new BreadcrumbItem('Product 1', '/products/1', true);

        // Assert
        $this->assertEquals('Home', $item1->libelle);
        $this->assertEquals('#', $item1->lien);
        $this->assertFalse($item1->isActive);

        $this->assertEquals('Products', $item2->libelle);
        $this->assertEquals('/products', $item2->lien);
        $this->assertFalse($item2->isActive);

        $this->assertEquals('Product 1', $item3->libelle);
        $this->assertEquals('/products/1', $item3->lien);
        $this->assertTrue($item3->isActive);
    }

    public function test_date_usgph_year_current_year_after_september()
    {
        // Arrange
        $currentDay = Carbon::create(2023, 10, 15); // October 15, 2023

        // Act
        $result = DateUSGPH::getUSGPHYear($currentDay);

        // Assert
        $this->assertEquals(Carbon::create(2023, 9, 1, 0, 0, 0), $result['start']);
        $this->assertEquals(Carbon::create(2024, 8, 31, 23, 59, 59), $result['end']);
    }

    public function test_date_usgph_year_current_year_before_september()
    {
        // Arrange
        $currentDay = Carbon::create(2023, 5, 15); // May 15, 2023

        // Act
        $result = DateUSGPH::getUSGPHYear($currentDay);

        // Assert
        $this->assertEquals(Carbon::create(2022, 9, 1, 0, 0, 0), $result['start']);
        $this->assertEquals(Carbon::create(2023, 8, 31, 23, 59, 59), $result['end']);
    }
}
