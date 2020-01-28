<?php

namespace App\Controller;

use App\Entity\Cover;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateCoverAction
{
    public function __invoke(Request $request): Cover
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $cover = new Cover();
        $cover->setFile($uploadedFile);

        return $cover;
    }
}
