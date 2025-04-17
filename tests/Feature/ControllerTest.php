<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class ControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Controller();
    }

    public function test_can_returns_true_when_user_has_ability()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        Bouncer::allow($user)->to('edit-posts');

        // Act
        $result = $this->controller->can('edit-posts');

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_returns_false_when_user_does_not_have_ability()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        // Ne pas donner la capacité 'edit-posts' à l'utilisateur

        // Act
        $result = $this->controller->can('edit-posts');

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_returns_false_when_no_user_is_logged_in()
    {
        // Arrange
        Auth::logout();

        // Act
        $result = $this->controller->can('edit-posts');

        // Assert
        $this->assertFalse($result);
    }

    public function test_isA_returns_true_when_user_has_role()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        Bouncer::assign('admin')->to($user);

        // Act
        $result = $this->controller->isA('admin');

        // Assert
        $this->assertTrue($result);
    }

    public function test_isA_returns_false_when_user_does_not_have_role()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        // Ne pas assigner le rôle 'admin' à l'utilisateur

        // Act
        $result = $this->controller->isA('admin');

        // Assert
        $this->assertFalse($result);
    }

    public function test_isA_returns_false_when_no_user_is_logged_in()
    {
        // Arrange
        Auth::logout();

        // Act
        $result = $this->controller->isA('admin');

        // Assert
        $this->assertFalse($result);
    }

    public function test_isA_with_multiple_roles()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        Bouncer::assign('editor')->to($user);
        Bouncer::assign('writer')->to($user);

        // Act & Assert
        $this->assertTrue($this->controller->isA('editor'));
        $this->assertTrue($this->controller->isA('writer'));
        $this->assertFalse($this->controller->isA('admin'));
    }

    public function test_isA_case_sensitivity()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        Bouncer::assign('admin')->to($user);

        // Act & Assert
        $this->assertTrue($this->controller->isA('admin'));
        $this->assertFalse($this->controller->isA('Admin')); // Bouncer is case-sensitive by default
    }
}
