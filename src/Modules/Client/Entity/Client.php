<?php

namespace Project\Modules\Client\Entity;

use Project\Common\Entity\Aggregate;
use Project\Modules\Client\Entity\Access\Access;
use Project\Modules\Client\Api\Events\Client\ClientUpdated;
use Project\Modules\Client\Api\Events\Client\ClientCreated;
use Project\Modules\Client\Api\Events\Confirmation\ClientConfirmationCreated;
use Project\Modules\Client\Api\Events\Confirmation\ClientConfirmationRefreshed;

class Client extends Aggregate
{
    private ClientId $id;
    private Name $name;
    private Contacts $contacts;
    private array $accesses = [];
    private array $confirmations = [];
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(ClientId $id, string $phone) {
        $this->id = $id;
        $this->name = new Name;
        $this->contacts = new Contacts($phone);
        $this->createdAt = new \DateTimeImmutable;
        $this->addEvent(new ClientCreated($this));
    }

    public function updateName(Name $name): void
    {
        if ($this->name->equalsTo($name)) {
            return;
        }

        $this->name = $name;
        $this->updated();
    }

    private function updated(): void
    {
        $this->updatedAt = new \DateTimeImmutable;
        $this->addEvent(new ClientUpdated($this));
    }

    public function updateEmail(?string $email): void
    {
        if ($this->contacts->getEmail() === $email) {
            return;
        }

        $this->contacts = $this->contacts->updateEmail($email);
        $this->updated();
    }

    public function confirmPhone(): void
    {
        $this->contacts = $this->contacts->confirmPhone();
        $this->updated();
    }

    public function confirmEmail(): void
    {
        $this->contacts = $this->contacts->confirmEmail();
        $this->updated();
    }

    public function addAccess(Access $access): void
    {
        foreach ($this->getAccesses() as $clientAccess) {
            if ($clientAccess->equalsTo($access)) {
                throw new \DomainException('Client already have same access');
            }
        }

        $this->accesses[] = $access;
    }

    public function removeAccess(Access $access): void
    {
        foreach ($this->getAccesses() as $index => $clientAccess) {
            if ($clientAccess->equalsTo($access)) {
                unset($this->accesses[$index]);
                return;
            }
        }

        throw new \DomainException('Client does not have provided access to delete');
    }

    public function hasAccess(Access $access): bool
    {
        foreach ($this->getAccesses() as $clientAccess) {
            if ($clientAccess->equalsTo($access)) {
                return true;
            }
        }

        return false;
    }

    public function generateConfirmation(
        Confirmation\CodeGeneratorInterface $codeGenerator,
        int $lifeTimeInMinutes = 5
    ): Confirmation\ConfirmationUuid {
        $confirmation = new Confirmation\Confirmation(
            Confirmation\ConfirmationUuid::random(),
            $codeGenerator->generate(),
            $lifeTimeInMinutes
        );

        $this->confirmations[$confirmation->getUuid()->getId()] = $confirmation;
        $this->addEvent(new ClientConfirmationCreated($this, $confirmation));
        return $confirmation->getUuid();
    }

    public function refreshConfirmationExpiredAt(
        Confirmation\ConfirmationUuid $uuid,
        int $lifeTimeInMinutes = 5
    ): void {
        $confirmation = $this->getConfirmation($uuid);
        $refreshed = $confirmation->refreshExpiredAt($lifeTimeInMinutes);
        $this->confirmations[$uuid->getId()] = $refreshed;
        $this->addEvent(new ClientConfirmationRefreshed($this, $refreshed));
    }

    public function getConfirmation(Confirmation\ConfirmationUuid $uuid): Confirmation\Confirmation
    {
        if (!isset($this->confirmations[$uuid->getId()])) {
            throw new \DomainException("Client does not have confirmation with uuid '$uuid'");
        }

        return $this->confirmations[$uuid->getId()];
    }

    public function applyConfirmation(Confirmation\ConfirmationUuid $uuid, int|string $inputCode): void
    {
        $confirmation = $this->getConfirmation($uuid);
        $confirmation->validateCode($inputCode);
        unset($this->confirmations[$uuid->getId()]);
    }

    public function hasConfirmation(Confirmation\ConfirmationUuid $uuid): bool
    {
        return isset($this->confirmations[$uuid->getId()]);
    }

    public function getId(): ClientId
    {
        return $this->id;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getContacts(): Contacts
    {
        return $this->contacts;
    }

    /**
     * @return Access[]
     */
    public function getAccesses(): array
    {
        return $this->accesses;
    }

    /**
     * @return Confirmation\Confirmation[]
     */
    public function getConfirmations(): array
    {
        return $this->confirmations;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}