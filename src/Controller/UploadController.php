<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use App\Form\UploadType;

use App\Entity\Upload;



// This controlle is responsible for managing the logic of the upload interface
#[Route('/', name: 'app_')]
class UploadController extends FrontController
{


    // This function is responsible for rendering the upload interface
    #[Route('/upload', name: 'upload')]
    public function index(): Response
    {
        return $this->render('/services/uploads/upload.html.twig', []);
    }

    // This function is responsible for rendering the uploaded files interface
    #[Route('/uploaded', name: 'uploaded_files')]
    public function uploaded_files(): Response
    {
        return $this->render(
            'services/uploads/uploaded.html.twig'
        );
    }
    // 
    // 
    // 
    //     
    // 
    // Create a route to upload a file, and pass the request to the UploadService to handle the file upload
    #[Route('/uploading', name: 'generic_upload_files')]
    public function generic_upload_files(Request $request): Response
    {
        $this->logger->info('fullrequest', ['request' => $request]);

        // Get the URL of the page from which the request originated
        $originUrl = $request->headers->get('referer');

        // Check if the URL contains the word "button" to bypass issue when uploading stuff directly from a button page
        if (strpos($originUrl, 'button') !== false) {    // Find the position of the word "button"
            $buttonPos = strpos($originUrl, 'button');

            // Find the position of the last '/' before "button"
            $slashPos = strrpos(substr($originUrl, 0, $buttonPos), '/');

            // Truncate the string to remove everything after the last '/'
            $originUrl = substr($originUrl, 0, $slashPos);
        }
        // Retrieve the User object
        $user = $this->getUser();
        // Retrieve the button and the newFileName from the request
        $button      = $request->request->get('button');
        $newFileName = $request->request->get('newFileName');
        // Find the Button entity in the repository based on its ID
        $buttonEntity = $this->buttonRepository->findoneBy(['id' => $button]);
        // Check if the file already exists by comparing the filename and the button
        $conflictFile = '';
        $filename     = '';
        $file         = $request->files->get('file');
        if ($newFileName) {
            $filename = $newFileName;
        } else {
            $filename = $file->getClientOriginalName();
        }
        $conflictFile = $this->uploadRepository->findOneBy(['button' => $buttonEntity, 'filename' => $filename]);
        // If the file already exists, return an error message
        if ($conflictFile) {
            $this->addFlash('error', 'Le fichier ' . $filename . ' existe déjà.');
            return $this->redirect($originUrl);
        } else {
            // Use the UploadService to handle file uploads
            $name = $this->uploadService->uploadFiles($request, $buttonEntity, $user, $newFileName);
            $this->addFlash('success', 'Le document ' . $name . ' a été correctement chargé');
            $this->logger->info('originURL', ['originURL' => $originUrl]);
            return $this->redirect($originUrl);
        }
    }
    // 
    // 
    // 
    // 
    // create a route to redirect to the correct views of a file
    #[Route('/download/{uploadId}', name: 'download_file')]
    public function filterDownloadFile(int $uploadId, Request $request): Response
    {
        $file = $this->uploadRepository->findOneBy(['id' => $uploadId]);
        $path = $file->getPath();

        if (!($this->settings->isUploadValidation() || $this->settings->IsTraining())) {
            return $this->downloadFileFromMethods($path);
        }
    
        $isValidated = $file->isValidated();
        $isForcedDisplay = $file->isForcedDisplay();
        $isTraining = $file->isTraining();
        $hasOldUpload = $file->getOldUpload() !== null;
        $originUrl = $request->headers->get('Referer');
    
        if ($isValidated === false && $isForcedDisplay === false) {
            if ($this->settings->IsTraining() && $isTraining) {
                return $this->redirectToRoute('app_training_front_by_validation', [
                    'validationId' => $file->getValidation()->getId()
                ]);
            } elseif ($hasOldUpload) {
                return $this->downloadFileFromPath($uploadId);
            } else {
                $this->addFlash(
                    'Danger',
                    'Le fichier a été refusé par les validateurs et son affichage n\'est pas forcé. Contacter votre responsable pour plus d\'informations.'
                );
                return $this->redirect($originUrl, 307);
            }
        }
    
        if ($isValidated === null && $isForcedDisplay === false) {
            if ($hasOldUpload) {
                return $this->downloadFileFromPath($uploadId);
            } else {
                $this->addFlash(
                    'Danger',
                    'Le fichier est en cours de validation et son affichage n\'est pas forcé. Contacter votre responsable pour plus d\'informations.'
                );
                return $this->redirect($originUrl, 307);
            }
        }
    
        if ($isValidated === true) {
            if ($this->settings->IsTraining() && $isTraining) {
                return $this->redirectToRoute('app_training_front_by_upload', [
                    'uploadId' => $uploadId
                ]);
            } else {
                return $this->downloadFileFromMethods($path);
            }
        }
    
        if ($isValidated === null) {  
            if ($this->settings->IsTraining() && $isTraining) {
                return $this->redirectToRoute('app_training_front_by_validation', [
                    'validationId' => $file->getValidation()->getId()
                ]);
            } else {
                return $this->downloadFileFromPath($uploadId);
            }
        }
    
        // Default action if none of the above conditions are met
        return $this->downloadFileFromMethods($path);
    }
    // 
    // 
    // 
    // 
    // create a route to download a file in more simple terms to display the file
    public function downloadFileFromMethods(string $path): Response
    {
        $this->logger->info('path', ['path' => $path]);
        $file = new File($path);
        $this->logger->info('file', ['file' => $file]);
        return $this->file($file, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    // 
    // 
    // 
    // 
    // create a route to redirect to the correct views of a file
    #[Route('/downloadByPath/{uploadId}', name: 'download_file_from_path')]
    public function downloadFileFromPath(int $uploadId): Response
    {
        $file = $this->uploadRepository->findOneBy(['id' => $uploadId]);

        $path = $this->determineFilePath($file);
        $this->logger->info('path', ['path' => $path]);
        return $this->downloadFileFromMethods($path);
    }
    // 
    // 
    // 
    // 
    // Private method to determine the file path
    private function determineFilePath($file): string
    {

        if (!$file->isValidated()) {
            $this->logger->info('is validated', ['validated' => $file->isValidated()]);
            if ($file->isForcedDisplay() === true || $file->isForcedDisplay() === null) {
                $this->logger->info('is forced display on or null', ['forcedDisplay' => $file->isForcedDisplay()]);
                return $file->getPath();
            } elseif ($file->getOldUpload() != null) {
                $this->logger->info('is old upload', ['oldUpload' => $file->getOldUpload()]);
                $oldUpload = $file->getOldUpload();
                return $oldUpload->getPath();
            }
        }
        return $file->getPath();
    }

    // 
    // 
    // 
    // 
    // create a route to download a file in more simple terms to display the file
    #[Route('/download/invalidation/{uploadId}', name: 'download_invalidation_file')]
    public function downloadInvalidationFile(int $uploadId = null, Request $request): Response
    {
        // Retrieve the origin URL
        $originUrl = $request->headers->get('Referer');
        $file = $this->uploadRepository->findOneBy(['id' => $uploadId]);
        $path = $file->getPath();
        $file = new File($path);
        return $this->file($file, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    // 
    // 
    // 
    // 
    // create a route to delete a file
    #[Route('/delete/upload/{uploadId}', name: 'delete_file')]

    public function deleteFile(int $uploadId = null, Request $request): RedirectResponse
    {
        $originUrl = $request->headers->get('Referer');
        $upload = $this->uploadRepository->findOneBy(['id' => $uploadId]);

        // Check if the user is the creator of the upload or if he is a super admin
        if ($this->authChecker->isGranted("ROLE_LINE_ADMIN") || $this->getUser() === $upload->getUploader() || $upload->getUploader() === null) {
            // Use the UploadService to handle file deletion
            $name = $this->entitydeletionService->deleteFile($uploadId);
        } else {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer ce document.');
            return $this->redirect($originUrl);
        }

        $this->addFlash('success', 'Le fichier  ' . $name . ' a été supprimé.');
        return $this->redirect($originUrl);
    }
    // 
    // 
    // 
    // 
    // create a route to modify a file and or display the modification page
    #[Route('/modification/view/{uploadId}', name: 'modify_file')]
    public function fileModificationView(Request $request, int $uploadId): Response
    {
        // Retrieve the current upload entity based on the uploadId
        $upload      = $this->uploadRepository->findOneBy(['id' => $uploadId]);
        $button      = $upload->getButton();
        $category    = $button->getCategory();
        $productLine = $category->getProductLine();
        $zone        = $productLine->getZone();

        // Create a form to modify the Upload entity
        $form = $this->createForm(UploadType::class, $upload);

        $currentUser = $this->security->getUser();
        $uploader = $upload->getUploader();
        // If it's a GET request, render the form
        if ($request->isMethod('GET') && ($currentUser === $uploader || $uploader === null || $this->authChecker->isGranted("ROLE_ADMIN"))) {
            return $this->render('services/uploads/uploads_modification.html.twig', [
                'form'        => $form->createView(),
                'zone'        => $zone,
                'productLine' => $productLine,
                'category'    => $category,
                'button'      => $button,
                'upload'      => $upload
            ]);
        } else {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier ce fichier. Contacter un administrateur ou le service informatique.');
            return $this->redirectToRoute('app_base');
        }
    }
    // 
    // 
    // 
    // 
    // Testing separting the post and get in two different method to see if the issue of not reloading the pages and persisting the comments can be resolved through
    // the reorganization of the code
    #[Route('/modification/modifying/{uploadId}', name: 'modifying_file')]
    public function modifyingFile(Request $request, int $uploadId): Response
    {
        // Log the request
        $this->logger->info('fullrequest', ['request' => $request->request->all()]);

        // Retrieve the current upload entity based on the uploadId
        $upload      = $this->uploadRepository->findOneBy(['id' => $uploadId]);
        $categoryId  = $upload->getButton()->getCategory()->getId();
        $oldFileName = $upload->getFilename();

        // Create a form to modify the Upload entity
        $form = $this->createForm(UploadType::class, $upload);

        // Retrieve the User object
        $user = $this->getUser();

        // Check if there is a file to modify
        if (!$upload) {
            $this->addFlash('error', 'Le fichier n\'a pas été trouvé.');
            return $this->redirectToRoute('app_category_admin', [
                'categoryId' => $categoryId
            ]);
        }

        $trainingNeeded =  $request->request->get('training-needed');
        $forcedDisplay = $request->request->get('display-needed');

        $this->logger->info('training needed', ['training' => $trainingNeeded]);
        $this->logger->info('forced display', ['display-needed' => $forcedDisplay]);

        $comment = $request->request->get('modificationComment');
        $newValidation = $request->request->get('validatorRequired');
        $enoughValidator = false;

        $enoughValidator = $request->request->has('validator_user4');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Process the form data and modify the Upload entity
            try {
                if ($trainingNeeded == null || $forcedDisplay == null) {
                    if ($upload->getFile() && $upload->getValidation() != null && $comment == null && $comment == "" && $request->request->get('modification-outlined' == '')) {
                        $this->addFlash('error', 'Le commentaire est vide. Commenter votre modification est obligatoire.');
                        return $this->redirectToRoute('app_category_admin', [
                            'categoryId' => $categoryId
                        ]);
                    } elseif ($newValidation == "true" && $enoughValidator == false) {
                        $this->addFlash('error', 'Selectionner au moins 4 validateurs pour valider le fichier.');
                        return $this->redirectToRoute('app_category_admin', [
                            'categoryId' => $categoryId
                        ]);
                    }
                }
                $this->uploadService->modifyFile($upload, $user, $request, $oldFileName);
                $this->addFlash('success', 'Le fichier a été modifié.');
                return $this->redirectToRoute('app_category_admin', [
                    'categoryId' => $categoryId
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('app_category_admin', [
                    'categoryId' => $categoryId
                ]);
            }
        }
        // Convert the errors to an array
        if ($form->isSubmitted() && !$form->isValid()) {
            // Return the errors in the JSON response
            $this->addFlash('error', 'Invalid form. Check the entered data.');
            return $this->redirectToRoute('app_category_admin', [
                'categoryId' => $categoryId
            ]);
        }
    }
}
