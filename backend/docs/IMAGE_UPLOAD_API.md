# Image Upload API Documentation

## Overview

Tu-Turismo implements secure image uploads for locations (Lugares), events (Eventos), and restaurants (Restaurantes). The image handling system includes:

- **Strict validation**: Only JPEG, PNG, and WebP formats are accepted
- **Size limits**: Maximum 2MB per file
- **Security**: Path traversal prevention, MIME validation, unique filename generation
- **Organization**: Images organized in subdirectories by type

## Supported Endpoints

### 1. Create Location with Image
**POST** `/api/v1/lugares`

```bash
curl -X POST http://localhost:8000/api/v1/lugares \
  -F "nombre=Palacio Nacional" \
  -F "descripcion=Beautiful palace in Mexico City" \
  -F "categoria_id=6765c4e123a4567890d1234b" \
  -F "latitud=19.4330" \
  -F "longitud=-99.1332" \
  -F "rating=4.8" \
  -F "imagen=@path/to/image.jpg"
```

**Required Fields:**
- `nombre` (string): Name of the location
- `descripcion` (string): Description
- `categoria_id` (string): MongoDB ObjectId of category
- `latitud` (number): Latitude coordinate
- `longitud` (number): Longitude coordinate
- `rating` (number): Rating (0-5)

**Optional Fields:**
- `imagen` (file): Image file (only JPEG, PNG, WebP)

**Success Response (201):**
```json
{
  "id": "6765d789abcdef1234567890",
  "nombre": "Palacio Nacional",
  "descripcion": "Beautiful palace in Mexico City",
  "categoria_id": "6765c4e123a4567890d1234b",
  "latitud": 19.4330,
  "longitud": -99.1332,
  "rating": 4.8,
  "imagen": "http://localhost:8000/storage/lugares/img_65c4e123a4567.8901_1710873600.jpg",
  "created_at": "2024-03-18T10:30:00Z",
  "updated_at": "2024-03-18T10:30:00Z"
}
```

**Error Response (422):**
```json
{
  "status": 422,
  "message": "El archivo de imagen es muy grande (máximo 2MB).",
  "errors": {
    "imagen": ["El archivo de imagen es muy grande (máximo 2MB)."]
  }
}
```

### 2. Update Location with New Image
**PUT** `/api/v1/lugares/{id}`

```bash
curl -X PUT http://localhost:8000/api/v1/lugares/6765d789abcdef1234567890 \
  -F "nombre=Palacio Nacional Updated" \
  -F "descripcion=Updated description" \
  -F "imagen=@path/to/new-image.png"
```

**Optional Fields:** Any field can be updated (all are optional with `sometimes` rule)

**Success Response (200):** Same format as create endpoint

### 3. Create Event with Image
**POST** `/api/v1/eventos`

```bash
curl -X POST http://localhost:8000/api/v1/eventos \
  -F "nombre=Expo 2024" \
  -F "descripcion=Annual expo" \
  -F "categoria_id=6765c4e123a4567890d1234b" \
  -F "inicio=2024-04-01T09:00:00Z" \
  -F "fin=2024-04-01T18:00:00Z" \
  -F "latitud=19.4330" \
  -F "longitud=-99.1332" \
  -F "imagen=@path/to/image.webp"
```

### 4. Create Restaurant with Image
**POST** `/api/v1/restaurantes`

```bash
curl -X POST http://localhost:8000/api/v1/restaurantes \
  -F "nombre=Taquería Fina" \
  -F "descripcion=Best tacos in town" \
  -F "categoria_id=6765c4e123a4567890d1234b" \
  -F "latitud=19.4330" \
  -F "longitud=-99.1332" \
  -F "precio_promedio=350" \
  -F "web=https://taqueria-fina.com" \
  -F "imagen=@path/to/image.jpg"
```

## Image Specifications

### Supported Formats
- **JPEG** (`.jpg`, `.jpeg`)
- **PNG** (`.png`)
- **WebP** (`.webp`)

### Size Limits
- **Maximum**: 2 MB (2048 KB)
- **Minimum**: No minimum (but must be valid image)

### MIME Type Validation
The system validates both the file extension and MIME type:

| Format | MIME Type(s) |
|--------|-------------|
| JPEG | `image/jpeg` |
| PNG | `image/png` |
| WebP | `image/webp` |

### Storage Location
Images are stored in subdirectories under `storage/app/public/`:
- Locations: `storage/app/public/lugares/`
- Events: `storage/app/public/eventos/`
- Restaurants: `storage/app/public/restaurantes/`
- Users: `storage/app/public/usuarios/`
- Categories: `storage/app/public/categorias/`

### Filename Format
Uploaded files are renamed to a secure format:
```
img_{uniqid}_{timestamp}.{extension}

Example:
img_65c4e123a4567.8901_1710873600.jpg
```

This prevents:
- Filename collisions and overwrites
- Path traversal attacks
- Enumeration of uploaded files

## Error Handling

### Validation Errors (422 Unprocessable Entity)

**Image too large:**
```json
{
  "status": 422,
  "message": "El archivo de imagen es muy grande (máximo 2MB).",
  "errors": {
    "imagen": ["El archivo de imagen es muy grande (máximo 2MB)."]
  }
}
```

**Invalid image format:**
```json
{
  "status": 422,
  "message": "El tipo de archivo de imagen no es permitido. Solo se aceptan: JPEG, PNG, WebP.",
  "errors": {
    "imagen": ["El tipo de archivo de imagen no es permitido. Solo se aceptan: JPEG, PNG, WebP."]
  }
}
```

**Invalid directory (path traversal attempt):**
```json
{
  "status": 422,
  "message": "El directorio de imagen no es permitido.",
  "errors": {
    "imagen": ["El directorio de imagen no es permitido."]
  }
}
```

### Server Errors (500)
```json
{
  "status": 500,
  "message": "Error storing image file.",
  "errors": {}
}
```

## Testing with cURL

### Test 1: Valid JPEG Upload
```bash
# Create a test JPEG (requires ImageMagick or similar)
convert -size 100x100 xc:blue test.jpg

# Upload it
curl -X POST http://localhost:8000/api/v1/lugares \
  -F "nombre=Test Lugar" \
  -F "descripcion=Test Description" \
  -F "categoria_id=6765c4e123a4567890d1234b" \
  -F "latitud=19.4330" \
  -F "longitud=-99.1332" \
  -F "rating=4.5" \
  -F "imagen=@test.jpg"
```

### Test 2: File Too Large (Should Fail)
```bash
# Create a file larger than 2MB
dd if=/dev/zero of=large.jpg bs=1M count=3

# Attempt upload (will be rejected)
curl -X POST http://localhost:8000/api/v1/lugares \
  -F "nombre=Test" \
  -F "descripcion=Test" \
  -F "categoria_id=6765c4e123a4567890d1234b" \
  -F "latitud=19.4330" \
  -F "longitud=-99.1332" \
  -F "rating=4.5" \
  -F "imagen=@large.jpg"
```

### Test 3: Invalid Format (Should Fail)
```bash
# Create a PDF
echo "PDF content" > test.pdf

# Attempt upload (will be rejected)
curl -X POST http://localhost:8000/api/v1/lugares \
  -F "nombre=Test" \
  -F "descripcion=Test" \
  -F "categoria_id=6765c4e123a4567890d1234b" \
  -F "latitud=19.4330" \
  -F "longitud=-99.1332" \
  -F "rating=4.5" \
  -F "imagen=@test.pdf"
```

### Test 4: Path Traversal Prevention (Should Fail)
The API will reject any attempt to store images in directories other than the allowed ones (lugares, eventos, restaurantes, usuarios, categorias).

## Testing with JavaScript/Fetch

### Create Location with Image
```javascript
const formData = new FormData();
formData.append('nombre', 'Museo de Arte');
formData.append('descripcion', 'Art museum in CDMX');
formData.append('categoria_id', '6765c4e123a4567890d1234b');
formData.append('latitud', 19.4330);
formData.append('longitud', -99.1332);
formData.append('rating', 4.8);

// Get image from input or URL
const fileInput = document.querySelector('input[type="file"]');
formData.append('imagen', fileInput.files[0]);

try {
  const response = await fetch('http://localhost:8000/api/v1/lugares', {
    method: 'POST',
    body: formData,
    // Don't set Content-Type header, browser will set it with boundary
  });

  if (response.ok) {
    const data = await response.json();
    console.log('Image URL:', data.imagen);
    console.log('Location ID:', data.id);
  } else {
    const error = await response.json();
    console.error('Error:', error.errors);
  }
} catch (err) {
  console.error('Network error:', err);
}
```

### Update Location with New Image
```javascript
const formData = new FormData();
formData.append('nombre', 'Updated Name');
// Only include fields you want to update

const fileInput = document.querySelector('input[type="file"]');
if (fileInput.files[0]) {
  formData.append('imagen', fileInput.files[0]);
}

const locationId = '6765d789abcdef1234567890';

const response = await fetch(`http://localhost:8000/api/v1/lugares/${locationId}`, {
  method: 'PUT',
  body: formData,
});

const data = await response.json();
console.log('Updated location:', data);
```

## Security Features

### 1. Double Validation
- **FormRequest level**: Laravel validation rules check MIME and size
- **Service level**: ImageService re-validates MIME, extension, size, and directory

### 2. Filename Sanitization
- Original filename is never used
- Generated filename is: `img_{uniqid}_{timestamp}.{extension}`
- `uniqid()` ensures uniqueness even with concurrent uploads
- Timestamp adds additional uniqueness

### 3. Directory Whitelist
Only these directories are allowed:
- `lugares`
- `eventos`
- `restaurantes`
- `usuarios`
- `categorias`

Any attempt to use other directories (including path traversal like `../../etc/`) is rejected.

### 4. MIME Type Validation
- Validates MIME type header in uploaded file
- Validates file extension matches expected extension
- Rejects if both don't match

### 5. Size Validation
- Enforced at FormRequest level (user-friendly error message)
- Re-validated at Service level (defense in depth)

## Troubleshooting

### Image upload returns 422 with "imagen not provided"
This usually means the file field name is incorrect. Make sure you're using the field name `imagen`.

### Image URL returns 404
Make sure:
1. The storage symlink exists: `php artisan storage:link`
2. The `storage/app/public/` directory has correct permissions
3. The storage is accessible via web server

### Large files are rejected
The maximum file size is 2MB. If you need larger images:
1. Compress the image first (use online tools or ImageMagick)
2. Or contact admins to increase the limit in `ImageService.php`

### Getting "extension not recognized"
Ensure:
1. File has correct extension (.jpg, .png, .webp)
2. File is actually a valid image in that format
3. The file's MIME type matches its extension

## Implementation Details

### ImageService (`app/Services/ImageService.php`)

**Public Methods:**
- `store(UploadedFile $file, string $directory): string`
  - Validates and stores image
  - Returns full public URL
  - Throws `InvalidArgumentException` on validation failure

- `delete(string $imagePath): bool`
  - Deletes image file from storage
  - Takes file path (not URL)

**Validation Rules:**
- MIME types: `image/jpeg`, `image/png`, `image/webp`
- Extensions: `jpeg`, `jpg`, `png`, `webp`
- Max size: 2048 KB (2 MB)
- Allowed directories: `lugares`, `eventos`, `restaurantes`, `usuarios`, `categorias`

### FormRequest Validation

Each model (Lugar, Evento, Restaurante) has FormRequest classes:
- `StoreLugarRequest`, `UpdateLugarRequest`
- `StoreEventoRequest`, `UpdateEventoRequest`
- `StoreRestauranteRequest`, `UpdateRestauranteRequest`

All use the validation rule:
```php
'imagen' => 'sometimes|image|mimes:jpeg,png,webp|max:2048'
```

### Exception Handling

All `InvalidArgumentException` thrown by ImageService are caught by the global exception handler in `bootstrap/app.php` and returned as JSON 422 responses.

## Future Improvements

1. **Image Processing**
   - Implement image cropping/resizing
   - Generate thumbnails
   - Optimize image compression

2. **Cloud Storage**
   - Migrate to AWS S3 or similar CDN
   - Implement CloudFront distribution
   - Reduce server storage load

3. **Advanced Validation**
   - Implement EXIF data stripping (privacy)
   - Virus scanning on upload
   - Optical character recognition (OCR) for spam detection

4. **Cleanup**
   - Implement scheduled task to remove orphaned images
   - Track image usage in database
   - Archive old images

5. **Analytics**
   - Track upload frequency and sizes
   - Monitor storage usage
   - Alert on suspicious patterns
