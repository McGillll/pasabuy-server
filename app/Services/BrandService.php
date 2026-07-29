<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\Brand\BrandRepositoryInterface;
use App\Interfaces\Brand\BrandServiceInterface;

class BrandService implements BrandServiceInterface
{
    public function __construct(
        protected BrandRepositoryInterface $brandRepository,
    ) {}

    public function all(object $payload)
    {
        return $this->brandRepository->all($payload);
    }

    public function findByUuid(string $uuid)
    {
        $brand = $this->brandRepository->findByUuid($uuid);

        if (! $brand) {
            throw new NotFoundException('Brand not found.');
        }

        return $brand;
    }

    public function create(object $payload)
    {
        return $this->brandRepository->create($payload);
    }

    public function update(string $uuid, object $payload)
    {
        $this->findByUuid($uuid);

        return $this->brandRepository->update($uuid, $payload);
    }

    public function delete(string $uuid)
    {
        $this->findByUuid($uuid);

        return $this->brandRepository->delete($uuid);
    }
}
