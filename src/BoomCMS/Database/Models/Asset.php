<?php

namespace BoomCMS\Database\Models;

use BoomCMS\Contracts\Models\Asset as AssetInterface;
use BoomCMS\Contracts\Models\Person as PersonInterface;
use BoomCMS\Support\Traits\Comparable;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\File\UploadedFile as File;

class Asset extends Model implements AssetInterface
{
    use Comparable;

    const ATTR_ID = 'id';
    const ATTR_TITLE = 'title';
    const ATTR_DESCRIPTION = 'description';
    const ATTR_TYPE = 'type';
    const ATTR_UPLOADED_BY = 'uploaded_by';
    const ATTR_UPLOADED_AT = 'uploaded_time';
    const ATTR_THUMBNAIL_ID = 'thumbnail_asset_id';
    const ATTR_CREDITS = 'credits';
    const ATTR_DOWNLOADS = 'downloads';

    public $table = 'assets';

    public $guarded = [
        self::ATTR_ID,
    ];

    public $timestamps = false;

    /**
     * @var PersinInterface
     */
    protected $uploadedBy;

    /**
     * @var AssetVersion
     */
    protected $latestVersion;

    protected $versionColumns = [
        'asset_id'   => '',
        'width'      => '',
        'height'     => '',
        'filesize'   => '',
        'filename'   => '',
        'edited_at'  => '',
        'edited_by'  => '',
        'version:id' => '',
        'extension'  => '',
    ];

    /**
     * @return string
     */
    public function directory()
    {
        return storage_path().'/boomcms/assets';
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->getId() && file_exists($this->getFilename());
    }

    /**
     * @return float
     */
    public function getAspectRatio()
    {
        if (!$this->getHeight()) {
            return 0;
        }

        return $this->getWidth() / $this->getHeight();
    }

    /**
     * @return string
     */
    public function getCredits()
    {
        return $this->{self::ATTR_CREDITS};
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->{self::ATTR_DESCRIPTION};
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->getLatestVersion()->getExtension();
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->directory().DIRECTORY_SEPARATOR.$this->getLatestVersionId();
    }

    /**
     * @return int
     */
    public function getFilesize()
    {
        return $this->getLatestVersion()->getFilesize();
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return (int) $this->getLatestVersion()->getHeight();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return  (int) $this->{self::ATTR_ID};
    }

    public function getEmbedHtml($height = null, $width = null)
    {
        $viewPrefix = 'boomcms::assets.embed.';
        $assetType = strtolower(class_basename($this->getType()));

        $viewName = View::exists($viewPrefix.$assetType) ?
            $viewPrefix.$assetType :
            $viewPrefix.'default';

        return View::make($viewName, [
            'asset'  => $this,
            'height' => $height,
            'width'  => $width,
        ]);
    }

    /**
     * @return DateTime
     */
    public function getLastModified()
    {
        return $this->getLatestVersion()->getEditedAt();
    }

    /**
     * @return AssetVersion
     */
    public function getLatestVersion()
    {
        if ($this->latestVersion === null) {
            $this->latestVersion = $this->versions()
                ->orderBy(AssetVersion::ATTR_ID, 'desc')
                ->first();
        }

        return $this->latestVersion;
    }

    public function getLatestVersionId()
    {
        return $this->getLatestVersion()->getId();
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->getLatestVersion()->getMimetype();
    }

    public function getOriginalFilename()
    {
        return $this->getLatestVersion()->getFilename();
    }

    public function getTags()
    {
        if ($this->tags === null) {
            $this->tags = DB::table('assets_tags')
                ->where('asset_id', '=', $this->getId())
                ->lists('tag');
        }

        return $this->tags;
    }

    /**
     * @return int
     */
    public function getThumbnailAssetId()
    {
        return (int) $this->{self::ATTR_THUMBNAIL_ID};
    }

    public function getThumbnail()
    {
        return $this->hasOne(static::class, 'thumbnail_asset_id', 'asset_id');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->{self::ATTR_TITLE};
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->{self::ATTR_TYPE};
    }

    /**
     * @return PersonInterface
     */
    public function getUploadedBy()
    {
        if ($this->uploadedBy === null) {
            $this->uploadedBy = $this->belongsTo(Person::class, 'uploaded_by')->first();
        }

        return $this->uploadedBy;
    }

    public function getUploadedTime()
    {
        return (new DateTime())->setTimestamp($this->{self::ATTR_UPLOADED_AT});
    }

    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return (int) $this->getLatestVersion()->getWidth();
    }

    /**
     * @return bool
     */
    public function hasPreviousVersions()
    {
        return $this->versions()
            ->where('id', '!=', $this->getLatestVersionId())
            ->exists();
    }

    public function hasThumbnail()
    {
        return $this->getThumbnailAssetId() > 0;
    }

    /**
     * @return $this
     */
    public function incrementDownloads()
    {
        $this->increment(self::ATTR_DOWNLOADS);

        return $this;
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        return $this->getType() == 'image';
    }

    public function createVersionFromFile(File $file)
    {
        if (!$this->getTitle()) {
            $this->setTitle($file->getClientOriginalName());
        }

        $this->setType(AssetHelper::typeFromMimetype($file->getMimeType()));

        list($width, $height) = getimagesize($file->getRealPath());
        preg_match('|\.([a-z]+)$|', $file->getClientOriginalName(), $extension);

        if (isset($extension[1])) {
            $extension = $extension[1];
        } elseif ($this->isImage()) {
            $extension = image_type_to_extension($file->getMimeType(), false);
        }

        $version = AssetVersion::create([
            'asset_id'  => $this->getId(),
            'extension' => $extension,
            'filesize'  => $file->getClientSize(),
            'filename'  => $file->getClientOriginalName(),
            'width'     => $width,
            'height'    => $height,
            'edited_at' => time(),
            'edited_by' => Auth::getPerson()->getId(),
            'mimetype'  => File::mime($file->getRealPath()),
        ]);

        $file->move(static::directory(), $version->id);

        return $this;
    }

    public function revertTo($versionId)
    {
        $version = AssetVersion::find($versionId);

        if ($version && $version->asset_id = $this->getId()) {
            $attrs = $version->toArray();
            unset($attrs['id']);
            $attrs['edited_at'] = time();
            $attrs['edited_by'] = Auth::getPerson()->getId();

            $version = VersionModel::create($attrs);

            copy(
                static::directory().DIRECTORY_SEPARATOR.$versionId,
                static::directory().DIRECTORY_SEPARATOR.$version->id
            );
        }

        return $this;
    }

    /**
     * @param string $credits
     *
     * @return $this
     */
    public function setCredits($credits)
    {
        $this->{self::ATTR_CREDITS} = $credits;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->{self::ATTR_DESCRIPTION} = $description;

        return $this;
    }

    /**
     * @param int $assetId
     *
     * @return $this
     */
    public function setThumbnailAssetId($assetId)
    {
        $this->{self::ATTR_THUMBNAIL_ID} = $assetId;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->{self::ATTR_TITLE} = $title;

        return $this;
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->{self::ATTR_TYPE} = $type;

        return $this;
    }

    /**
     * @param PersonInterface $person
     *
     * @return $this
     */
    public function setUploadedBy(PersonInterface $person)
    {
        $this->{self::ATTR_UPLOADED_BY} = $person->getId();

        return $this;
    }

    public function setUploadedTime(DateTime $time)
    {
        $this->{self::ATTR_UPLOADED_AT} = $time->getTimestamp();

        return $this;
    }

    public function scopeWithLatestVersion($query)
    {
        return $query
            ->select('assets.*')
            ->join('asset_versions as version', 'assets.id', '=', 'version.asset_id')
            ->leftJoin('asset_versions as av2', function ($query) {
                $query
                    ->on('av2.asset_id', '=', 'version.asset_id')
                    ->on('av2.id', '>', 'version.id');
            })
            ->whereNull('av2.id');
    }

    public function scopeWithVersion($query, $versionId)
    {
        return $query
            ->where('asset_versions.id', '=', $versionId)
            ->first();
    }

    public function versions()
    {
        return $this->hasMany(AssetVersion::class);
    }
}
