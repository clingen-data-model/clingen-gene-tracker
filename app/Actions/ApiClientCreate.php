<?php

namespace App\Actions;

use App\ApiClient;
use Ramsey\Uuid\Uuid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class ApiClientCreate
{
    use AsAction;

    public $commandSignature = 'api-client:create {name : Unique name for api client} {email : Contact email}';

    public function handle(string $name, string $email)
    {
        return ApiClient::create([
            'name' => $name,
            'contact_email' => $email,
            'uuid' => Uuid::uuid4()->toString()
        ]);
    }

    public function asCommand(Command $command)
    {
        $name = $command->argument('name');
        $email = $command->argument('email');

        $validator = Validator::make(compact('name', 'email'), [
            'name' => 'required|max:255|unique:api_clients,name',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            $errors = array_flatten($validator->errors()->getMessages());
            foreach ($errors as $msg) {
                $command->error($msg);
            }
            return;
        }

        $apiClient = $this->handle($name, $email);

        $command->info('New "API Client" '.$name.' with id = '.$apiClient->id.' has been created.');
    }
    


}
