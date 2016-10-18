<?php

namespace BoomCMS\Http\Controllers\Asset;

use BoomCMS\Database\Models\Asset;
use BoomCMS\Database\Models\Site;
use BoomCMS\Foundation\Http\ValidatesAssetUpload;
use BoomCMS\Http\Controllers\Controller;
use BoomCMS\Support\Facades\Asset as AssetFacade;
use BoomCMS\Support\Helpers;
use BoomCMS\Support\Helpers\Asset as AssetHelper;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    use ValidatesAssetUpload;

    protected $role = 'manageAssets';

    /**
     * @param Asset $asset
     */
    public function destroy(Asset $asset)
    {
        AssetFacade::delete([$asset->getId()]);
    }

    // Needs to not require the manageAssets role. Move to another controller.
    public function index(Request $request)
    {
        return [
            'total'  => Helpers::countAssets($request->input()),
            'assets' => Helpers::getAssets($request->input()),
        ];
    }

    /**
     * @param Request $request
     * @param Asset   $asset
     *
     * @return JsonResponse
     */
    public function replace(Request $request, Asset $asset)
    {
        list($validFiles, $errors) = $this->validateAssetUpload($request);

        foreach ($validFiles as $file) {
            $asset->setType(AssetHelper::typeFromMimetype($file->getMimeType()));

            AssetFacade::save($asset);
            AssetFacade::createVersionFromFile($asset, $file);

            return $this->show($asset);
        }

        if (count($errors)) {
            return new JsonResponse($errors, 500);
        }
    }

    /**
     * @param Request $request
     * @param Asset   $asset
     */
    public function revert(Request $request, Asset $asset)
    {
        AssetFacade::revert($asset, $request->input('version_id'));

        return $this->show($asset);
    }

    /**
     * @param Site $site
     *
     * @return JsonResponse|array
     */
    public function store(Request $request, Site $site)
    {
        $assetIds = [];

        list($validFiles, $errors) = $this->validateAssetUpload($request);

        foreach ($validFiles as $file) {
            $asset = new Asset();
            $asset
                ->setSite($site)
                ->setUploadedTime(new DateTime('now'))
                ->setUploadedBy(Auth::user())
                ->setTitle($file->getClientOriginalName())
                ->setType(AssetHelper::typeFromMimetype($file->getMimeType()));

            $assetIds[] = AssetFacade::save($asset)->getId();
            AssetFacade::createVersionFromFile($asset, $file);
        }

        return (count($errors)) ? new JsonResponse($errors, 500) : $assetIds;
    }

    /**
     * @param Asset $asset
     */
    public function show(Asset $asset)
    {
        return $asset
            ->newQuery()
            ->with('versions')
            ->with('versions.editedBy')
            ->with('uploadedBy')
            ->with('albums')
            ->find($asset->getId());
    }

    /**
     * @param Request $request
     * @param Asset   $asset
     */
    public function update(Request $request, Asset $asset)
    {
        $asset
            ->setTitle($request->input(Asset::ATTR_TITLE))
            ->setDescription($request->input(Asset::ATTR_DESCRIPTION))
            ->setCredits($request->input(Asset::ATTR_CREDITS))
            ->setThumbnailAssetId($request->input(Asset::ATTR_THUMBNAIL_ID));

        AssetFacade::save($asset);

        return $this->show($asset);
    }
}
