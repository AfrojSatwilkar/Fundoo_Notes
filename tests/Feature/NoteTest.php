<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NoteTest extends TestCase
{
    //create notes success
    public function test_IfGiven_TitleAndDescription_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTY0MTg3NTQ3MCwiZXhwIjoxNjQxODc5MDcwLCJuYmYiOjE2NDE4NzU0NzAsImp0aSI6Ilc2N0liS2ZLRkoyRmw0UkMiLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.zkgoyjFb71UY1ZG-yMO5rj9QfeNdW6pes2GRXav-z20'
        ])->json('POST', '/api/createnote',
        [
            "title" => "afroj",
            "description" => "hello",
        ]);

        $response->assertStatus(201)->assertJson(['message' => 'notes created successfully']);
    }

    //create notes error status
    public function test_IfGiven_WrongAccessToken_ShouldReturnInvalidStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTYzMzM2OTU5NCwiZXhwIjoxNjMzMzczMTk0LCJuYmYiOjE2MzMzNjk1OTQsImp0aSI6InQ0MHo5djlXNFNGOUZRblEiLCJzdWIiOjEsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.t09kohx5hV15u8aAH-WXAsdnzmh9HsAqCOLci9QKyQ'
        ])->json('POST', '/api/createnote',
        [
            "title" => "title test one",
            "description" => "description test one",
        ]);

        $response->assertStatus(404)->assertJson(['message' => 'Invalid authorization token']);
    }

     //get all notes success
     public function test_IfGiven_AuthorisedToken_AndReturnAllNotes_SuccessStatus()
     {
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTY0MTg3NTQ3MCwiZXhwIjoxNjQxODc5MDcwLCJuYmYiOjE2NDE4NzU0NzAsImp0aSI6Ilc2N0liS2ZLRkoyRmw0UkMiLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.zkgoyjFb71UY1ZG-yMO5rj9QfeNdW6pes2GRXav-z20'
         ])->json('GET', '/api/readnote');

         $response->assertStatus(201)->assertJson(['message' => 'Fetched Notes Successfully']);
     }


     //get all notes Error
     public function test_IfGiven_WrongAuthorisedToken_AndReturnInvalid_ErrorStatus()
     {
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTYzMzQxMTQxMCwiZXhwIjoxNjMzNDE1MDEwLCJuYmYiOjE2MzM0MTE0MTAsImp0aSI6Imd5WThtclFFWG50N2JDUTgiLCJzdWIiOjEsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.b4CReGFIWLimgv0auC4sRTEHn8S6cZ8t9L6H_rNqwz'
         ])->json('GET', '/api/readnote');

         $response->assertStatus(404)->assertJson(['message' => 'Invalid authorization token']);
     }

     //update notes success status
    public function test_IfGiven_Id_TitleAndDescription_ShouldValidate_AndReturnSuccess_UpdateStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTY0MTg3NTQ3MCwiZXhwIjoxNjQxODc5MDcwLCJuYmYiOjE2NDE4NzU0NzAsImp0aSI6Ilc2N0liS2ZLRkoyRmw0UkMiLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.zkgoyjFb71UY1ZG-yMO5rj9QfeNdW6pes2GRXav-z20'
        ])->json('POST', '/api/editnote',
        [
            "id" => 1,
            "title" => "title update",
            "description" => "description update",
        ]);
        $response->assertStatus(201)->assertJson(['message' => 'Note successfully updated']);
    }

     //update notes Error status
     public function test_IfGiven_WrongId_TitleAndDescription_ShouldValidate_AndReturnNotes_NotFoundStatus()
     {
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTYzNTQ4MzM4NiwiZXhwIjoxNjM1NDg2OTg2LCJuYmYiOjE2MzU0ODMzODYsImp0aSI6IlJ6VUpsWWdtQ2VUdmFYUUUiLCJzdWIiOjEwLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.CjJ80kSAVmbT8rPHBfkxmgH94PmfEdMnSU63KsnrEb4'
         ])->json('POST', '/api/editnote',
         [
             "id" => "10",
             "title" => "titleupdate",
             "description" => "description test one update",
         ]);
         $response->assertStatus(404)->assertJson(['message' => 'Notes not Found']);
     }

     //delete success status
     public function test_IfGiven_Id_ShouldValidate_AndReturn_Delete_SuccessStatus()
     {
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTY0MTg3NTQ3MCwiZXhwIjoxNjQxODc5MDcwLCJuYmYiOjE2NDE4NzU0NzAsImp0aSI6Ilc2N0liS2ZLRkoyRmw0UkMiLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.zkgoyjFb71UY1ZG-yMO5rj9QfeNdW6pes2GRXav-z20'
         ])->json('POST', '/api/deletenote',
         [
             "id" => 1,
         ]);
         $response->assertStatus(201)->assertJson(['message' => 'Note successfully deleted']);
     }


     //delete error status
     public function test_IfGiven_WrongId_ShouldValidate_AndReturnNotes_NotFoundStatus()
     {
         $response = $this->withHeaders([
             'Content-Type' => 'Application/json',
             'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTYzNTQ4MzM4NiwiZXhwIjoxNjM1NDg2OTg2LCJuYmYiOjE2MzU0ODMzODYsImp0aSI6IlJ6VUpsWWdtQ2VUdmFYUUUiLCJzdWIiOjEwLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.CjJ80kSAVmbT8rPHBfkxmgH94PmfEdMnSU63KsnrEb4'
         ])->json('POST', '/api/deletenote',
         [
             "id" => "20",
         ]);
         $response->assertStatus(404)->assertJson(['message' => 'Notes not Found']);
     }

     /**
     * @test
     * for Successfull Pinned of note
     * to given note Id
     */
    public function test_Successfull_Pinned_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/pinnote',
            [
                "id" => "3"
            ]
        );
        $response->assertStatus(201)->assertJson(['message' => 'Note Pinned Sucessfully']);
    }

    /**
     * @test
     * for UnSuccessfull Pinned of note
     * to given note Id
     */
    public function test_UnSuccessfull_Pinned_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/pinnote',
            [
                "id" => "7"
            ]
        );
        $response->assertStatus(404)->assertJson(['message' => 'Notes not Found']);
    }

    /**
     * @test
     * for Successfull archived of note
     * to given note Id
     */
    public function test_Successfull_Archived_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/archivenote',
            [
                "id" => "3"
            ]
        );
        $response->assertStatus(201)->assertJson(['message' => 'Note Archived Sucessfully']);
    }
    /**
     * @test
     * for UnSuccessfull archived of note
     * to given note Id
     */
    public function test_UnSuccessfull_Archived_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/archivenote',
            [
                "id" => "7"
            ]
        );
        $response->assertStatus(404)->assertJson(['message' => 'Notes not Found']);
    }

    /**
     * @test
     * for Successfull Colour of note
     * to given note Id
     */
    public function test_Successfull_Coloured_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/colournote',
            [
                "id" => "9",
                "colour" => "blue",
            ]
        );
        $response->assertStatus(201)->assertJson(['message' => 'Note coloured Sucessfully']);
    }
    /**
     * @test
     * for UnSuccessfull Colour of note
     * to given note Id
     */
    public function test_UnSuccessfull_Coloured_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/colournote',
            [
                "id" => "8",
                "colour" => "blue",
            ]
        );
        $response->assertStatus(404)->assertJson(['message' => 'Notes not Found']);
    }

    /**
     * @test
     * for Successfull Search of note
     * to given anything
     */
    public function test_Successfull_Search_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/searchnotes',
            [
                "search" => "p"
            ]
        );
        $response->assertStatus(201)->assertJson(['message' => 'Fetched Notes Successfully']);
    }
    /**
     * @test
     * for UnSuccessfull Search of note
     * to given anything
     */
    public function test_UnSuccessfull_Search_Note()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTY0MjU2NzMwMSwiZXhwIjoxNjQyNTcwOTAxLCJuYmYiOjE2NDI1NjczMDEsImp0aSI6IjZFZTFpS1FqZHd1NjIzR08iLCJzdWIiOjksInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.3tXavu4g9QVlS9byH215sMC3VjQZIbvpnjc2EgJvw9o'
        ])->json(
            'POST',
            '/api/searchnotes',
            [
                "search" => "p"
            ]
        );
        $response->assertStatus(403)->assertJson(['message' => 'Invalid authorization token']);
    }
}
