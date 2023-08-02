<?php

namespace App\Entity;

use App\Repository\ApprobationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApprobationRepository::class)]
class Approbation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'approbations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Validation $Validation = null;

    #[ORM\ManyToOne(inversedBy: 'approbations')]
    private ?User $UserApprobator = null;

    #[ORM\ManyToOne(inversedBy: 'approbations')]
    private ?Department $DepartmentApprobator = null;

    #[ORM\Column(nullable: true)]
    private ?bool $Approval = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValidation(): ?Validation
    {
        return $this->Validation;
    }

    public function setValidation(?Validation $Validation): static
    {
        $this->Validation = $Validation;

        return $this;
    }

    public function getUserApprobator(): ?User
    {
        return $this->UserApprobator;
    }

    public function setUserApprobator(?User $UserApprobator): static
    {
        $this->UserApprobator = $UserApprobator;

        return $this;
    }

    public function getDepartmentApprobator(): ?Department
    {
        return $this->DepartmentApprobator;
    }

    public function setDepartmentApprobator(?Department $DepartmentApprobator): static
    {
        $this->DepartmentApprobator = $DepartmentApprobator;

        return $this;
    }

    public function isApproval(): ?bool
    {
        return $this->Approval;
    }

    public function setApproval(?bool $Approval): static
    {
        $this->Approval = $Approval;

        return $this;
    }
}
