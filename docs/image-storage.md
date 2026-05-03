# Image Storage Configuration for HotelMaint Pro

## Current Configuration

Your application is configured to use **Local Storage** by default, which is the optimal setup for SiteGround hosting.

### Configuration Details
- **Disk Driver**: `public` (local filesystem)
- **Storage Location**: `storage/app/public`
- **Public Access**: Via symbolic link at `public/storage`
- **URL Format**: `https://yourdomain.com/storage/filename.jpg`

## How It Works

1. When users upload images (complaint photos, asset photos, etc.), files are saved to `storage/app/public`
2. Laravel creates a symbolic link from `public/storage` to `storage/app/public`
3. Images become publicly accessible via your domain URL

## SiteGround Deployment Steps

### Step 1: Upload Your Code
Upload all files to your SiteGround hosting via FTP or SSH.

### Step 2: Create Storage Link
Connect via SSH and run:
```bash
cd /path/to/your/app
php artisan storage:link
```

This creates the necessary symbolic link for public access to uploaded images.

### Step 3: Set Directory Permissions
Ensure the storage directory is writable:
```bash
chmod -R 775 storage
chown -R www-data:www-data storage
```

On SiteGround, you can also set permissions via Site Tools → File Manager.

### Step 4: Verify Configuration
Check your `.env` file has:
```env
FILESYSTEM_DISK=public
```

And `config/filesystems.php` has the public disk configured:
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

## Testing Image Uploads

1. Log into your application
2. Create a new complaint with a photo attachment
3. Verify the image appears in the complaint details
4. Check that the image URL resolves correctly: `https://yourdomain.com/storage/complaints/xxxx.jpg`

## Troubleshooting

### Images Not Showing
- Run `php artisan storage:link` again
- Check file permissions on `storage/app/public`
- Verify `.htaccess` allows access to storage files

### Permission Denied Errors
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Broken Image Links
- Ensure the symbolic link exists: `ls -la public/storage`
- Recreate if needed: `rm public/storage && php artisan storage:link`

## Future Scaling: Moving to Cloud Storage

If your hotel grows and you need more storage scalability, you can easily switch to Amazon S3 or other cloud storage:

### Step 1: Install S3 Driver
```bash
composer require league/flysystem-aws-s3-v3
```

### Step 2: Update .env
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

### Step 3: Update Config
No code changes needed! Laravel's filesystem abstraction handles the switch automatically.

## Best Practices for SiteGround

1. **Regular Backups**: Use SiteGround's backup tools to include the `storage` folder
2. **Monitor Disk Space**: Check usage in Site Tools → Statistics
3. **Image Optimization**: Consider adding an image optimization package for large uploads
4. **CDN Integration**: For faster global access, integrate SiteGround's CDN with your storage

## Support

For issues specific to SiteGround hosting, refer to their documentation:
- [SiteGround File Permissions](https://www.siteground.com/kb/file-permissions/)
- [SiteGround SSH Access](https://www.siteground.com/kb/ssh-access/)
- [SiteGround Backups](https://www.siteground.com/kb/backups/)
