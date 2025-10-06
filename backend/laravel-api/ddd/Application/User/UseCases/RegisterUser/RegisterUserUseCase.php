<?php

declare(strict_types=1);

namespace Ddd\Application\User\UseCases\RegisterUser;

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Infrastructure\Services\Events\EventBus;
use Ddd\Infrastructure\Services\TransactionManager\TransactionManager;
use Ddd\Shared\Exceptions\EmailAlreadyExistsException;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepository $userRepository,
        private TransactionManager $transactionManager,
        private EventBus $eventBus
    ) {}

    public function execute(RegisterUserInput $input): RegisterUserOutput
    {
        // Check if email already exists
        if ($this->userRepository->existsByEmail($input->email)) {
            throw EmailAlreadyExistsException::forEmail($input->email->value());
        }

        $userId = $this->transactionManager->run(function () use ($input) {
            // Generate new ID
            $id = $this->userRepository->nextId();

            // Register user
            $user = User::register(
                id: $id,
                email: $input->email,
                name: $input->name
            );

            // Save user
            $this->userRepository->save($user);

            // Dispatch domain events after commit
            foreach ($user->pullDomainEvents() as $event) {
                $this->eventBus->dispatch($event, afterCommit: true);
            }

            return $id;
        });

        return new RegisterUserOutput($userId);
    }
}
