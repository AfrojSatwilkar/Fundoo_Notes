<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    //user registration success test
    public function test_IfGiven_UserCredentials_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/register', [
            "firstname" => "afroj",
            "lastname" => "satwilkar",
            "email" => "abcdefg@gmail.com",
            "password" => "afroj786",
            "confirm_password" => "afroj786"
        ]);
        $response->assertStatus(201)->assertJson(['message' => 'User successfully registered']);
    }

     //user registration Error test
     public function test_IfGiven_UserCredentialsSame_ShouldValidate_AndReturnErrorStatus()
     {
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
         ])->json('POST', '/api/register',
         [
             "firstname" => "zaheer",
             "lastname" => "gadkari",
             "email" => "afrozsatvilkar2014@gmail.com",
             "password" => "afroj786",
             "confirm_password" => "afroj786"
         ]);

         $response->assertStatus(401)->assertJson([
             'message' => 'The email has already been taken'
            ]);
     }

     //login success test
    public function test_IfGiven_LoginCredentials_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/login',
        [
            "email" => "afrozsatvilkar@gmail.com",
            "password" => "afroj786",
        ]);

        $response->assertStatus(201)->assertJson([
            // 'access_token' => $this->token,
            'message' => 'login Success',
            'token_type' => 'bearer'
        ]);
    }

    //login error status
    public function test_IfGiven_NotRegistered_LoginCredentials_ShouldValidate_AndReturnErrorStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/login',
        [
            "email" => "abc@gmail.com",
            "password" => "afrooj",
        ]);

        $response->assertStatus(401)->assertJson(['error' => 'we can not find the user with that e-mail address You need to register first']);
    }

     //logout success status
     public function test_IfGiven_AccessToken_ShouldValidate_AndReturnSuccessStatus()
     {
         $this->withoutExceptionHandling();
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTY0MTgyNjk0MiwiZXhwIjoxNjQxODMwNTQyLCJuYmYiOjE2NDE4MjY5NDIsImp0aSI6ImpGY0lRMzl1dk9nWW1DanYiLCJzdWIiOjUsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.nSguVM__nzB4rwnG-UrZjKOVNDmgbPNHV6B4NzbsHlE;'
         ])->json('POST', '/api/logout');
         $response->assertStatus(201)->assertJson(['message'=> 'User successfully logged out']);
     }

     //logout error test
     public function test_IfGiven_WrongAccessToken_ShouldValidate_AndReturnErrorStatus()
     {
         $this->withoutExceptionHandling();
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTYzNDYzNjIyMCwiZXhwIjoxNjM0NjM5ODIwLCJuYmYiOjE2MzQ2MzYyMjAsImp0aSI6IlNjeWFhekF0b1prVldZMXUiLCJzdWIiOjcsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.FAd0DyV1sM3shANnfXsqaA2qHPX0JWqd5LKoYH_Vj5'
         ])->json('POST', '/api/logout');
         $response->assertStatus(404)->assertJson(['message'=> 'Invalid authorization token']);
     }

     //forgot passsword success
    public function test_IfGiven_Registered_EmailId_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/forgotpassword', [
            "email" => "afrozsatvilkar2014@gmail.com"
        ]);

        $response->assertStatus(200)->assertJson(['message'=> 'we have mailed your password reset link to respective E-mail']);
    }

    //forgot password failure
    public function test_IfGiven_WrongEmailId_ShouldValidate_AndReturnErrorStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/forgotpassword',
        [
            "email" => "xyz@gmail.com",
        ]);
        $response->assertStatus(404)->assertJson(['message' => 'we can not find a user with that email address']);
    }

    //reset password success
    public function test_IfGiven_NewAndConfirmPassword_ShouldValidate_AndSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL2FwaVwvZm9yZ290cGFzc3dvcmQiLCJpYXQiOjE2NDE4MjgzMTQsImV4cCI6MTY0MTgzMTkxNCwibmJmIjoxNjQxODI4MzE0LCJqdGkiOiJYVWVndzlwOEtCMjZBcnNKIiwic3ViIjoyLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.U_iFCrT6t8LVTNvll-7UAVCFmiytUGO5e8QnUOEyfx4',
        ])->json('POST', '/api/resetpassword',
        [
            "new_password" => "123456",
            "confirm_password" => "123456",
        ]);
        $response->assertStatus(201)->assertJson(['message' => 'Password reset successfull!']);
    }

    //reset password failure
    public function test_IfGiven_NewAndConfirmPasswordAndWrongToken_ShouldValidate_AndReturnErrorStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => ' Bearer eyJ0eXAiO1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL2FwaVwvZm9yZ290cGFzc3dvcmQiLCJpYXQiOjE2NDE4MjgzMTQsImV4cCI6MTY0MTgzMTkxNCwibmJmIjoxNjQxODI4MzE0LCJqdGkiOiJYVWVndzlwOEtCMjZBcnNKIiwic3ViIjoyLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.U_iFCrT6t8LVTNvll-7UAVCFmiytUGO5e8QnUOEyfx4',
        ])->json('POST', '/api/resetpassword',
        [
            "new_password" => "nithin123",
            "confirm_password" => "nithin123",
        ]);
        $response->assertStatus(401)->assertJson(['message' => 'This token is invalid']);
    }
}
