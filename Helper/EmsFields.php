<?php

namespace EMS\CommonBundle\Helper;

final class EmsFields
{
    const CONTENT_MIME_TYPE_FIELD = 'mimetype';
    const CONTENT_FILE_HASH_FIELD = 'sha1';
    const CONTENT_FILE_SIZE_FIELD = 'filesize';
    const CONTENT_FILE_NAME_FIELD = 'filename';
    const CONTENT_MIME_TYPE_FIELD_ = '_mime_type';
    const CONTENT_FILE_HASH_FIELD_ = '_hash';
    const CONTENT_FILE_ALGO_FIELD_ = '_algo';
    const CONTENT_FILE_SIZE_FIELD_ = '_file_size';
    const CONTENT_FILE_NAME_FIELD_ = '_filename';
    const CONTENT_FILE_NAMES = '_file_names';
    const CONTENT_HASH_ALGO_FIELD = '_hash_algo';
    const CONTENT_PUBLISHED_DATETIME_FIELD = '_published_datetime';


    const ASSET_CONFIG_DISPOSITION = '_disposition';
    const ASSET_CONFIG_BACKGROUND = '_background';
    const ASSET_CONFIG_TYPE = '_config_type';
    const ASSET_CONFIG_TYPE_IMAGE = 'image';
    const ASSET_CONFIG_GRAVITY = '_gravity';
    const ASSET_CONFIG_MIME_TYPE = '_mime_type';
    const ASSET_CONFIG_FILE_NAMES = '_file_names';
    const ASSET_CONFIG_HEIGHT = '_height';
    const ASSET_CONFIG_QUALITY = '_quality';
    const ASSET_CONFIG_RESIZE = '_resize';
    const ASSET_CONFIG_WIDTH = '_width';
    const ASSET_CONFIG_RADIUS = '_radius';
    const ASSET_CONFIG_RADIUS_GEOMETRY = '_radius_geometry';
    const ASSET_CONFIG_BORDER_COLOR = '_border_color';
    const ASSET_CONFIG_WATERMARK_HASH = '_watermark_hash';
    const ASSET_CONFIG_GET_FILE_PATH = '_get_file_path';
}
