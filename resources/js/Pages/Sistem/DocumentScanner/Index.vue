<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user_role: {
        type: String,
        default: 'user'
    },
    presets: {
        type: Object,
        default: () => ({})
    },
    ocr_languages: {
        type: Array,
        default: () => [
            { code: 'ind', name: 'Bahasa Indonesia (ind)' },
            { code: 'eng', name: 'Bahasa Inggris (eng)' }
        ]
    }
})

// Tab State
const activeTab = ref('editor') // 'editor' | 'queue' | 'ocr'

// Status & Loader State
const openCvLoaded = ref(false)
const isProcessing = ref(false)
const statusText = ref('Menginisialisasi OpenCV...')
const statusType = ref('processing') // 'processing' | 'ready' | 'error'

// Mode State
const isBookMode = ref(false)
const isSpineActive = ref(false)
const spineX = ref(0.5)

// Image & Calibration State
const originalFileSize = ref(0)
const originalImageDimensions = ref({ width: 0, height: 0 })
const outputDimensions = ref({ width: 0, height: 0 })
const rotationAngle = ref(0)
const hasAutoRotated = ref(false)

// Parameter Adjustments
const activeFilter = ref('color') // 'magic' | 'color' | 'gray' | 'bw'
const resolutionPreset = ref('standard') // 'standard' | 'hd' | 'super-hd'
const jpegQuality = ref(0.85)
const sharpenVal = ref(0.8)
const brightnessVal = ref(10)
const contrastVal = ref(1.40)

// Compression Stats
const compressedSize = ref(0)
const compressedSizeFormatted = computed(() => {
    if (!compressedSize.value) return '-'
    if (compressedSize.value >= 1024 * 1024) {
        return (compressedSize.value / (1024 * 1024)).toFixed(2) + ' MB'
    }
    return (compressedSize.value / 1024).toFixed(1) + ' KB'
})

const originalSizeFormatted = computed(() => {
    if (!originalFileSize.value) return '-'
    if (originalFileSize.value >= 1024 * 1024) {
        return (originalFileSize.value / (1024 * 1024)).toFixed(2) + ' MB'
    }
    return (originalFileSize.value / 1024).toFixed(1) + ' KB'
})

const savingsRatio = computed(() => {
    if (!originalFileSize.value || !compressedSize.value) return '-'
    const saved = ((originalFileSize.value - compressedSize.value) / originalFileSize.value) * 100
    if (saved > 0) return `${saved.toFixed(1)}% Hemat`
    return '0%'
})

// Corner Normalized Coordinates (0.0 to 1.0)
const corners = ref({
    tl: { x: 0.1, y: 0.1 },
    tr: { x: 0.9, y: 0.1 },
    br: { x: 0.9, y: 0.9 },
    bl: { x: 0.1, y: 0.9 }
})

// Book Right Page Corners (0.0 to 1.0)
const cornersRight = ref({
    tl: { x: 0.55, y: 0.1 },
    tr: { x: 0.95, y: 0.1 },
    br: { x: 0.95, y: 0.9 },
    bl: { x: 0.55, y: 0.9 }
})

// Dragging Handle State
const activeDragCorner = ref(null)
const isDraggingSpine = ref(false)

// Zoom & Pan
const zoomScale = ref(1.0)
const isMagnifierActive = ref(false)
const magnifierPos = ref({ x: 0, y: 0 })

// PDF & Batch Queue State
const pdfFilename = ref('Dokumen_Scan_SINTA')
const pdfPageQueue = ref([])
const isGeneratingPdf = ref(false)

// OCR State
const isOcrActive = ref(false)
const ocrLanguage = ref('ind')
const isOcrRunning = ref(false)
const ocrProgressStatus = ref('Menyiapkan Tesseract...')
const ocrProgressPercent = ref(0)
const ocrResultText = ref('')

// Camera Scanner Modal State
const showCameraModal = ref(false)
const cameraStream = ref(null)
const cameraVideoEl = ref(null)
const cameraOverlayCanvas = ref(null)
const cameraStatusText = ref('Posisikan dokumen dalam bingkai kamera...')
let cameraProcessRaf = null
let stableFrameCount = 0
let prevCamPoints = null

// Stored Blobs for Download
let singleResultBlob = null
let leftResultBlob = null
let rightResultBlob = null
let originalOpenCvMat = null

// Canvas Refs
const originalCanvasRef = ref(null)
const outputCanvasRef = ref(null)
const outputLeftCanvasRef = ref(null)
const outputRightCanvasRef = ref(null)
const magnifierCanvasRef = ref(null)
const fileInputRef = ref(null)
const originalWrapperRef = ref(null)

// Toast Notifications
const toastList = ref([])
const showToast = (message, type = 'info') => {
    const id = Date.now() + Math.random()
    toastList.value.push({ id, message, type })
    setTimeout(() => {
        toastList.value = toastList.value.filter(t => t.id !== id)
    }, 4000)
}

// -------------------------------------------------------------
// SCRIPT LOADERS (OpenCV.js, jsPDF, Tesseract.js)
// -------------------------------------------------------------
const loadExternalScript = (src, id) => {
    return new Promise((resolve, reject) => {
        if (document.getElementById(id)) {
            resolve()
            return
        }
        const script = document.createElement('script')
        script.id = id
        script.src = src
        script.async = true
        script.onload = () => resolve()
        script.onerror = (e) => reject(e)
        document.head.appendChild(script)
    })
}

onMounted(async () => {
    // 1. Load jsPDF
    try {
        await loadExternalScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', 'jspdf-script')
    } catch (e) {
        console.warn('jsPDF load warning:', e)
    }

    // 2. Load OpenCV.js
    if (window.cv && window.cv.Mat) {
        openCvLoaded.value = true
        statusText.value = 'Sistem Siap'
        statusType.value = 'ready'
    } else {
        window.Module = {
            onRuntimeInitialized: () => {
                openCvLoaded.value = true
                statusText.value = 'Sistem Siap'
                statusType.value = 'ready'
                showToast('OpenCV.js berhasil dimuat secara client-side!', 'success')
            }
        }
        try {
            await loadExternalScript('https://docs.opencv.org/4.5.4/opencv.js', 'opencv-script')
        } catch (err) {
            console.error('OpenCV load error:', err)
            statusText.value = 'Gagal memuat OpenCV'
            statusType.value = 'error'
        }
    }

    window.addEventListener('resize', handleWindowResize)
})

onUnmounted(() => {
    stopCamera()
    window.removeEventListener('resize', handleWindowResize)
    if (originalOpenCvMat) {
        try { originalOpenCvMat.delete() } catch (e) {}
        originalOpenCvMat = null
    }
})

const handleWindowResize = () => {
    if (originalImageDimensions.value.width > 0) {
        fitOriginalCanvas(originalImageDimensions.value.width, originalImageDimensions.value.height)
    }
}

// -------------------------------------------------------------
// OPENCV CORNER DETECTION & PERSPECTIVE WARP ALGORITHMS
// -------------------------------------------------------------
const detectCornersForMat = (src, offsetX = 0) => {
    if (!window.cv) return null
    const cv = window.cv
    const maxDim = 600
    const scale = Math.min(maxDim / src.cols, maxDim / src.rows, 1.0)

    let resized = new cv.Mat()
    let dsize = new cv.Size(Math.round(src.cols * scale), Math.round(src.rows * scale))
    cv.resize(src, resized, dsize, 0, 0, cv.INTER_AREA)

    let gray = new cv.Mat()
    let blurred = new cv.Mat()
    let edged = new cv.Mat()

    cv.cvtColor(resized, gray, cv.COLOR_RGBA2GRAY)
    cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0)
    cv.Canny(blurred, edged, 40, 150)

    let kernel = cv.getStructuringElement(cv.MORPH_RECT, new cv.Size(3, 3))
    cv.dilate(edged, edged, kernel)

    let contours = new cv.MatVector()
    let hierarchy = new cv.Mat()
    cv.findContours(edged, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE)

    let maxArea = 0
    let bestPoly = null

    for (let i = 0; i < contours.size(); ++i) {
        let cnt = contours.get(i)
        let area = cv.contourArea(cnt)
        if (area > (resized.cols * resized.rows * 0.08)) {
            let peri = cv.arcLength(cnt, true)
            let approx = new cv.Mat()
            cv.approxPolyDP(cnt, approx, 0.02 * peri, true)

            if (approx.rows === 4 && area > maxArea) {
                if (cv.isContourConvex(approx)) {
                    maxArea = area
                    if (bestPoly) bestPoly.delete()
                    bestPoly = approx.clone()
                }
            }
            approx.delete()
        }
        cnt.delete()
    }

    let detectedPts = null
    if (bestPoly) {
        let pts = []
        for (let j = 0; j < 4; j++) {
            pts.push({
                x: (bestPoly.data32S[j * 2] / scale) + offsetX,
                y: bestPoly.data32S[j * 2 + 1] / scale
            })
        }
        detectedPts = orderQuadPoints(pts)
        bestPoly.delete()
    }

    resized.delete(); gray.delete(); blurred.delete(); edged.delete()
    kernel.delete(); contours.delete(); hierarchy.delete()

    return detectedPts
}

const orderQuadPoints = (pts) => {
    let sortedByY = [...pts].sort((a, b) => a.y - b.y)
    let topTwo = [sortedByY[0], sortedByY[1]].sort((a, b) => a.x - b.x)
    let bottomTwo = [sortedByY[2], sortedByY[3]].sort((a, b) => a.x - b.x)

    return {
        tl: topTwo[0],
        tr: topTwo[1],
        br: bottomTwo[1],
        bl: bottomTwo[0]
    }
}

const autoDetectSpine = (grayMat) => {
    const width = grayMat.cols
    const height = grayMat.rows
    const startCol = Math.floor(width * 0.22)
    const endCol = Math.floor(width * 0.78)

    let colSums = []
    let overallSum = 0

    for (let x = 0; x < width; x++) {
        let sum = 0
        for (let y = 0; y < height; y++) {
            sum += grayMat.ucharAt(y, x)
        }
        colSums[x] = sum / height
        overallSum += colSums[x]
    }

    const overallAvg = overallSum / width
    let minVal = 999999
    let detectedX = -1

    for (let x = startCol; x < endCol; x++) {
        if (colSums[x] < minVal) {
            minVal = colSums[x]
            detectedX = x
        }
    }

    if (detectedX !== -1 && minVal < overallAvg * 0.88) {
        return detectedX / width
    }
    return 1.0
}

const runSpineDetection = (mat) => {
    if (!window.cv) return
    const cv = window.cv
    let gray = new cv.Mat()
    let small = new cv.Mat()
    cv.cvtColor(mat, gray, cv.COLOR_RGBA2GRAY)
    cv.resize(gray, small, new cv.Size(200, 150), 0, 0, cv.INTER_AREA)

    const detectedX = autoDetectSpine(small)
    gray.delete()
    small.delete()

    if (detectedX < 0.95 && detectedX > 0.05) {
        spineX.value = detectedX
        isSpineActive.value = true
        showToast('Lipatan buku (spine) terdeteksi otomatis.', 'info')
    } else {
        if (!isBookMode.value) {
            isSpineActive.value = false
            spineX.value = 1.0
        } else {
            spineX.value = 0.5
        }
    }
}

const detectCorners = (mat) => {
    const imgW = mat.cols
    const imgH = mat.rows

    if (!isBookMode.value) {
        const detected = detectCornersForMat(mat, 0)
        if (detected) {
            corners.value = {
                tl: { x: Math.min(detected.tl.x / imgW, spineX.value), y: detected.tl.y / imgH },
                tr: { x: Math.min(detected.tr.x / imgW, spineX.value), y: detected.tr.y / imgH },
                br: { x: Math.min(detected.br.x / imgW, spineX.value), y: detected.br.y / imgH },
                bl: { x: Math.min(detected.bl.x / imgW, spineX.value), y: detected.bl.y / imgH }
            }
        } else {
            corners.value = {
                tl: { x: 0.05, y: 0.05 },
                tr: { x: Math.min(0.95, spineX.value), y: 0.05 },
                br: { x: Math.min(0.95, spineX.value), y: 0.95 },
                bl: { x: 0.05, y: 0.95 }
            }
        }
    } else {
        // Book Mode: Split Left & Right
        corners.value = {
            tl: { x: 0.05, y: 0.08 },
            tr: { x: spineX.value, y: 0.08 },
            br: { x: spineX.value, y: 0.92 },
            bl: { x: 0.05, y: 0.92 }
        }
        cornersRight.value = {
            tl: { x: spineX.value, y: 0.08 },
            tr: { x: 0.95, y: 0.08 },
            br: { x: 0.95, y: 0.92 },
            bl: { x: spineX.value, y: 0.92 }
        }
    }
}

// -------------------------------------------------------------
// PERSPECTIVE WARP & COLOR ENHANCEMENT ENGINE
// -------------------------------------------------------------
const warpAndFilterMat = (srcMat, quadPoints, targetCanvas, targetW, targetH) => {
    if (!window.cv) return
    const cv = window.cv

    let srcCoords = cv.matFromArray(4, 1, cv.CV_32FC2, [
        quadPoints.tl.x, quadPoints.tl.y,
        quadPoints.tr.x, quadPoints.tr.y,
        quadPoints.br.x, quadPoints.br.y,
        quadPoints.bl.x, quadPoints.bl.y
    ])

    let dstCoords = cv.matFromArray(4, 1, cv.CV_32FC2, [
        0, 0,
        targetW, 0,
        targetW, targetH,
        0, targetH
    ])

    let M = cv.getPerspectiveTransform(srcCoords, dstCoords)
    let warped = new cv.Mat()
    let dsize = new cv.Size(targetW, targetH)
    cv.warpPerspective(srcMat, warped, M, dsize, cv.INTER_CUBIC, cv.BORDER_REPLICATE, new cv.Scalar())

    // Adjust Brightness & Contrast
    let adjusted = new cv.Mat()
    warped.convertTo(adjusted, -1, contrastVal.value, brightnessVal.value)

    // Filter Processing
    let processed = new cv.Mat()
    if (activeFilter.value === 'magic') {
        let lab = new cv.Mat()
        cv.cvtColor(adjusted, lab, cv.COLOR_RGBA2RGB)
        let channels = new cv.MatVector()
        cv.split(lab, channels)
        let clahe = new cv.CLAHE(2.0, new cv.Size(8, 8))
        let claheDst = new cv.Mat()
        clahe.apply(channels.get(0), claheDst)
        channels.set(0, claheDst)
        cv.merge(channels, lab)
        cv.cvtColor(lab, processed, cv.COLOR_RGB2RGBA)

        lab.delete(); channels.delete(); clahe.delete(); claheDst.delete()
    } else if (activeFilter.value === 'gray') {
        let gray = new cv.Mat()
        cv.cvtColor(adjusted, gray, cv.COLOR_RGBA2GRAY)
        cv.cvtColor(gray, processed, cv.COLOR_GRAY2RGBA)
        gray.delete()
    } else if (activeFilter.value === 'bw') {
        let gray = new cv.Mat()
        cv.cvtColor(adjusted, gray, cv.COLOR_RGBA2GRAY)
        let bw = new cv.Mat()
        cv.adaptiveThreshold(gray, bw, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY, 21, 10)
        cv.cvtColor(bw, processed, cv.COLOR_GRAY2RGBA)
        gray.delete(); bw.delete()
    } else {
        // 'color' standard
        processed = adjusted.clone()
    }

    // USM Sharpening
    if (sharpenVal.value > 0) {
        let blurred = new cv.Mat()
        cv.GaussianBlur(processed, blurred, new cv.Size(0, 0), 3)
        cv.addWeighted(processed, 1 + sharpenVal.value, blurred, -sharpenVal.value, 0, processed)
        blurred.delete()
    }

    cv.imshow(targetCanvas, processed)

    srcCoords.delete(); dstCoords.delete(); M.delete()
    warped.delete(); adjusted.delete(); processed.delete()
}

// -------------------------------------------------------------
// MAIN RENDER & COMPRESS WORKFLOW
// -------------------------------------------------------------
let debounceProcessTimer = null
const processAndCompress = (isFastPreview = false) => {
    if (!originalOpenCvMat || !openCvLoaded.value) return

    clearTimeout(debounceProcessTimer)
    debounceProcessTimer = setTimeout(() => {
        isProcessing.value = true
        statusText.value = 'Memproses Hasil Scan...'
        statusType.value = 'processing'

        const imgW = originalOpenCvMat.cols
        const imgH = originalOpenCvMat.rows

        let targetW = 1200
        let targetH = 1842
        if (resolutionPreset.value === 'hd') {
            targetW = 1600; targetH = 2456
        } else if (resolutionPreset.value === 'super-hd') {
            targetW = 2400; targetH = 3684
        }
        outputDimensions.value = { width: targetW, height: targetH }

        if (!isBookMode.value) {
            const pts = {
                tl: { x: corners.value.tl.x * imgW, y: corners.value.tl.y * imgH },
                tr: { x: corners.value.tr.x * imgW, y: corners.value.tr.y * imgH },
                br: { x: corners.value.br.x * imgW, y: corners.value.br.y * imgH },
                bl: { x: corners.value.bl.x * imgW, y: corners.value.bl.y * imgH }
            }

            if (outputCanvasRef.value) {
                warpAndFilterMat(originalOpenCvMat, pts, outputCanvasRef.value, targetW, targetH)
                outputCanvasRef.value.toBlob((blob) => {
                    singleResultBlob = blob
                    compressedSize.value = blob ? blob.size : 0
                    isProcessing.value = false
                    statusText.value = 'Sistem Siap'
                    statusType.value = 'ready'
                }, 'image/jpeg', jpegQuality.value)
            }
        } else {
            // Book Dual Mode
            const ptsL = {
                tl: { x: corners.value.tl.x * imgW, y: corners.value.tl.y * imgH },
                tr: { x: corners.value.tr.x * imgW, y: corners.value.tr.y * imgH },
                br: { x: corners.value.br.x * imgW, y: corners.value.br.y * imgH },
                bl: { x: corners.value.bl.x * imgW, y: corners.value.bl.y * imgH }
            }
            const ptsR = {
                tl: { x: cornersRight.value.tl.x * imgW, y: cornersRight.value.tl.y * imgH },
                tr: { x: cornersRight.value.tr.x * imgW, y: cornersRight.value.tr.y * imgH },
                br: { x: cornersRight.value.br.x * imgW, y: cornersRight.value.br.y * imgH },
                bl: { x: cornersRight.value.bl.x * imgW, y: cornersRight.value.bl.y * imgH }
            }

            if (outputLeftCanvasRef.value && outputRightCanvasRef.value) {
                warpAndFilterMat(originalOpenCvMat, ptsL, outputLeftCanvasRef.value, targetW, targetH)
                warpAndFilterMat(originalOpenCvMat, ptsR, outputRightCanvasRef.value, targetW, targetH)

                outputLeftCanvasRef.value.toBlob((blobL) => {
                    leftResultBlob = blobL
                    outputRightCanvasRef.value.toBlob((blobR) => {
                        rightResultBlob = blobR
                        compressedSize.value = (blobL?.size || 0) + (blobR?.size || 0)
                        isProcessing.value = false
                        statusText.value = 'Sistem Siap'
                        statusType.value = 'ready'
                    }, 'image/jpeg', jpegQuality.value)
                }, 'image/jpeg', jpegQuality.value)
            }
        }
    }, isFastPreview ? 60 : 10)
}

const fitOriginalCanvas = (imgW, imgH) => {
    const container = document.getElementById('original-canvas-container')
    if (!container || !originalWrapperRef.value || !originalCanvasRef.value) return

    const maxW = container.clientWidth - 32
    const maxH = 500

    let ratio = Math.min(maxW / imgW, maxH / imgH)
    let displayW = Math.round(imgW * ratio)
    let displayH = Math.round(imgH * ratio)

    originalWrapperRef.value.style.width = `${displayW}px`
    originalWrapperRef.value.style.height = `${displayH}px`

    originalCanvasRef.value.style.width = '100%'
    originalCanvasRef.value.style.height = '100%'
}

// -------------------------------------------------------------
// FILE UPLOAD & IMAGE ROTATION
// -------------------------------------------------------------
const triggerFileInput = () => {
    if (fileInputRef.value) fileInputRef.value.click()
}

const handleFileUpload = (e) => {
    const files = e.target.files
    if (!files || files.length === 0) return

    if (files.length > 1) {
        handleMultipleFiles(files)
    } else {
        loadFileToCanvas(files[0])
    }
}

const loadFileToCanvas = (file) => {
    if (!file.type.match('image.*')) {
        showToast('Berkas harus berformat gambar (JPG/PNG)!', 'error')
        return
    }

    originalFileSize.value = file.size
    isProcessing.value = true
    statusText.value = 'Membaca Gambar...'
    statusType.value = 'processing'

    const reader = new FileReader()
    reader.onload = (e) => {
        const img = new Image()
        img.onload = () => {
            if (!originalCanvasRef.value) return
            originalCanvasRef.value.width = img.width
            originalCanvasRef.value.height = img.height
            const ctx = originalCanvasRef.value.getContext('2d')
            ctx.drawImage(img, 0, 0)

            originalImageDimensions.value = { width: img.width, height: img.height }

            if (originalOpenCvMat) originalOpenCvMat.delete()
            originalOpenCvMat = window.cv.imread(originalCanvasRef.value)

            fitOriginalCanvas(img.width, img.height)
            runSpineDetection(originalOpenCvMat)
            detectCorners(originalOpenCvMat)
            processAndCompress()
            showToast('Dokumen berhasil dimuat & tepian terdeteksi otomatis!', 'success')
        }
        img.src = e.target.result
    }
    reader.readAsDataURL(file)
}

const handleMultipleFiles = async (files) => {
    showToast(`Memproses ${files.length} berkas ke antrean batch...`, 'info')
    for (let i = 0; i < files.length; i++) {
        const file = files[i]
        await new Promise((resolve) => {
            const reader = new FileReader()
            reader.onload = (e) => {
                const img = new Image()
                img.onload = () => {
                    const tempCanvas = document.createElement('canvas')
                    tempCanvas.width = img.width
                    tempCanvas.height = img.height
                    const ctx = tempCanvas.getContext('2d')
                    ctx.drawImage(img, 0, 0)

                    let tempMat = window.cv.imread(tempCanvas)
                    let pts = detectCornersForMat(tempMat, 0)
                    if (!pts) {
                        pts = {
                            tl: { x: img.width * 0.05, y: img.height * 0.05 },
                            tr: { x: img.width * 0.95, y: img.height * 0.05 },
                            br: { x: img.width * 0.95, y: img.height * 0.95 },
                            bl: { x: img.width * 0.05, y: img.height * 0.95 }
                        }
                    }

                    let targetW = 1200; let targetH = 1842
                    const outC = document.createElement('canvas')
                    warpAndFilterMat(tempMat, pts, outC, targetW, targetH)

                    const dataUrl = outC.toDataURL('image/jpeg', jpegQuality.value)
                    pdfPageQueue.value.push({
                        id: Date.now() + Math.random().toString(36).substring(2, 9),
                        filename: file.name,
                        canvasDataURL: dataUrl,
                        blobSize: Math.round(dataUrl.length * 0.75)
                    })

                    tempMat.delete()
                    resolve()
                }
                img.src = e.target.result
            }
            reader.readAsDataURL(file)
        })
    }
    showToast(`Selesai! ${files.length} halaman ditambahkan ke antrean PDF.`, 'success')
    // Also load the first file into active editor
    if (files.length > 0) loadFileToCanvas(files[0])
}

const rotateImage = () => {
    if (!originalOpenCvMat || !window.cv) return
    const cv = window.cv
    let rotated = new cv.Mat()
    cv.rotate(originalOpenCvMat, rotated, cv.ROTATE_90_CLOCKWISE)
    originalOpenCvMat.delete()
    originalOpenCvMat = rotated

    originalCanvasRef.value.width = originalOpenCvMat.cols
    originalCanvasRef.value.height = originalOpenCvMat.rows
    cv.imshow(originalCanvasRef.value, originalOpenCvMat)

    originalImageDimensions.value = { width: originalOpenCvMat.cols, height: originalOpenCvMat.rows }
    fitOriginalCanvas(originalOpenCvMat.cols, originalOpenCvMat.rows)
    detectCorners(originalOpenCvMat)
    processAndCompress()
    showToast('Gambar diputar 90 derajat.', 'info')
}

const resetCorners = () => {
    if (!originalOpenCvMat) return
    detectCorners(originalOpenCvMat)
    processAndCompress()
    showToast('Posisi sudut diatur ulang ke deteksi otomatis.', 'info')
}

// -------------------------------------------------------------
// DEMO DOCUMENT GENERATOR (Programmatic Sample)
// -------------------------------------------------------------
const loadDemoDocument = () => {
    isProcessing.value = true
    statusText.value = 'Membuat Dokumen Demo...'
    statusType.value = 'processing'

    const demoCanvas = document.createElement('canvas')
    demoCanvas.width = 2400
    demoCanvas.height = 1600
    const ctx = demoCanvas.getContext('2d')

    // Table background gradient
    const tableGrad = ctx.createLinearGradient(0, 0, 2400, 1600)
    tableGrad.addColorStop(0, '#1a100a')
    tableGrad.addColorStop(1, '#0c0704')
    ctx.fillStyle = tableGrad
    ctx.fillRect(0, 0, 2400, 1600)

    // Wood grain lines
    ctx.strokeStyle = 'rgba(0,0,0,0.6)'
    ctx.lineWidth = 14
    for (let i = 150; i < 2400; i += 300) {
        ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i + 120, 1600); ctx.stroke()
    }

    ctx.save()
    ctx.translate(1200, 800)
    ctx.rotate(0.05)

    // Paper Shadow & Body
    ctx.shadowColor = 'rgba(0, 0, 0, 0.7)'
    ctx.shadowBlur = 40
    ctx.shadowOffsetX = 15
    ctx.shadowOffsetY = 24
    ctx.fillStyle = '#faf8f2'
    ctx.fillRect(-450, -600, 900, 1200)
    ctx.shadowColor = 'transparent'

    // Margin Line & Rules
    ctx.strokeStyle = 'rgba(220, 38, 38, 0.25)'
    ctx.lineWidth = 2.5
    ctx.beginPath(); ctx.moveTo(-310, -550); ctx.lineTo(-310, 550); ctx.stroke()

    ctx.strokeStyle = '#e2e8f0'
    ctx.lineWidth = 1.2
    for (let y = -450; y < 500; y += 40) {
        ctx.beginPath(); ctx.moveTo(-380, y); ctx.lineTo(380, y); ctx.stroke()
    }

    // Title & Texts
    ctx.fillStyle = '#0f172a'
    ctx.font = 'bold 36px sans-serif'
    ctx.fillText('DOKUMEN RESMI SINTA-SAAS', -280, -500)

    ctx.fillStyle = '#334155'
    ctx.font = '500 17px sans-serif'
    const demoLines = [
        'AeroScan - Pemindai Dokumen Kinerja Tinggi',
        'Simulasi berkas digital resolusi tinggi (100% Client-Side Privacy).',
        '',
        '- Pemindaian otomatis tepi dokumen dengan OpenCV.js',
        '- Perspektif warp meluruskan orientasi kertas yang miring',
        '- Kompresi cerdas menghemat penyimpanan server hingga 90%',
        '- Ekstraksi teks multi-bahasa OCR (Bahasa Indonesia & Inggris)',
        '- Penggabungan multi-halaman PDF terstandarisasi',
        '',
        'SINTA SaaS © 2026 - All Rights Reserved.'
    ]

    let textY = -400
    demoLines.forEach(line => {
        if (line.includes('AeroScan') || line.includes('SINTA SaaS')) {
            ctx.font = 'bold 18px sans-serif'
            ctx.fillStyle = '#1e293b'
        } else {
            ctx.font = '500 17px sans-serif'
            ctx.fillStyle = '#475569'
        }
        ctx.fillText(line, -280, textY)
        textY += 40
    })

    // Simulated Signature & Stamp
    ctx.strokeStyle = '#2563eb'
    ctx.lineWidth = 3
    ctx.beginPath(); ctx.moveTo(-280, 200); ctx.bezierCurveTo(-260, 180, -270, 220, -230, 205); ctx.stroke()
    ctx.beginPath(); ctx.moveTo(-230, 205); ctx.bezierCurveTo(-210, 185, -200, 230, -160, 200); ctx.stroke()

    ctx.restore()

    originalFileSize.value = 5242880 // ~5MB
    originalImageDimensions.value = { width: 2400, height: 1600 }

    if (originalCanvasRef.value) {
        originalCanvasRef.value.width = 2400
        originalCanvasRef.value.height = 1600
        const oCtx = originalCanvasRef.value.getContext('2d')
        oCtx.drawImage(demoCanvas, 0, 0)

        if (originalOpenCvMat) originalOpenCvMat.delete()
        originalOpenCvMat = window.cv.imread(originalCanvasRef.value)

        fitOriginalCanvas(2400, 1600)
        runSpineDetection(originalOpenCvMat)
        detectCorners(originalOpenCvMat)
        processAndCompress()
        showToast('Dokumen demo beresolusi tinggi berhasil dimuat!', 'success')
    }
}

// -------------------------------------------------------------
// INTERACTIVE DRAG CORNER HANDLERS & MAGNIFIER
// -------------------------------------------------------------
const startCornerDrag = (cornerKey, e) => {
    e.preventDefault()
    activeDragCorner.value = cornerKey
    isMagnifierActive.value = true

    window.addEventListener('mousemove', onCornerMouseMove)
    window.addEventListener('touchmove', onCornerTouchMove, { passive: false })
    window.addEventListener('mouseup', stopCornerDrag)
    window.addEventListener('touchend', stopCornerDrag)
}

const onCornerMouseMove = (e) => {
    updateCornerPosition(e.clientX, e.clientY)
}

const onCornerTouchMove = (e) => {
    if (e.touches && e.touches.length > 0) {
        updateCornerPosition(e.touches[0].clientX, e.touches[0].clientY)
    }
}

const updateCornerPosition = (clientX, clientY) => {
    if (!activeDragCorner.value || !originalWrapperRef.value) return
    const rect = originalWrapperRef.value.getBoundingClientRect()

    let normX = (clientX - rect.left) / rect.width
    let normY = (clientY - rect.top) / rect.height
    normX = Math.max(0, Math.min(1.0, normX))
    normY = Math.max(0, Math.min(1.0, normY))

    const key = activeDragCorner.value
    if (['tl', 'tr', 'br', 'bl'].includes(key)) {
        if (isSpineActive.value || isBookMode.value) {
            normX = Math.min(normX, spineX.value)
        }
        corners.value[key] = { x: normX, y: normY }
    } else if (['rtl', 'rtr', 'rbr', 'rbl'].includes(key)) {
        const rKey = key.replace('r', '')
        normX = Math.max(normX, spineX.value)
        cornersRight.value[rKey] = { x: normX, y: normY }
    }

    // Update Magnifier
    magnifierPos.value = { x: clientX, y: clientY }
    renderMagnifier(normX, normY)
}

const renderMagnifier = (normX, normY) => {
    if (!originalCanvasRef.value || !magnifierCanvasRef.value) return
    const srcCtx = originalCanvasRef.value.getContext('2d')
    const magCtx = magnifierCanvasRef.value.getContext('2d')

    const srcW = originalCanvasRef.value.width
    const srcH = originalCanvasRef.value.height
    const pixelX = normX * srcW
    const pixelY = normY * srcH

    const cropSize = 80
    magCtx.clearRect(0, 0, 120, 120)
    magCtx.drawImage(
        originalCanvasRef.value,
        pixelX - cropSize / 2, pixelY - cropSize / 2, cropSize, cropSize,
        0, 0, 120, 120
    )

    // Draw Crosshair
    magCtx.strokeStyle = '#2563eb'
    magCtx.lineWidth = 2
    magCtx.beginPath()
    magCtx.moveTo(60, 20); magCtx.lineTo(60, 100)
    magCtx.moveTo(20, 60); magCtx.lineTo(100, 60)
    magCtx.stroke()
}

const stopCornerDrag = () => {
    activeDragCorner.value = null
    isMagnifierActive.value = false
    window.removeEventListener('mousemove', onCornerMouseMove)
    window.removeEventListener('touchmove', onCornerTouchMove)
    window.removeEventListener('mouseup', stopCornerDrag)
    window.removeEventListener('touchend', stopCornerDrag)

    processAndCompress()
}

// -------------------------------------------------------------
// SPINE DRAG HANDLER
// -------------------------------------------------------------
const startSpineDrag = (e) => {
    e.preventDefault()
    isDraggingSpine.value = true
    window.addEventListener('mousemove', onSpineMouseMove)
    window.addEventListener('touchmove', onSpineTouchMove, { passive: false })
    window.addEventListener('mouseup', stopSpineDrag)
    window.addEventListener('touchend', stopSpineDrag)
}

const onSpineMouseMove = (e) => {
    if (!isDraggingSpine.value || !originalWrapperRef.value) return
    const rect = originalWrapperRef.value.getBoundingClientRect()
    let x = (e.clientX - rect.left) / rect.width
    spineX.value = Math.max(0.15, Math.min(0.85, x))
}

const onSpineTouchMove = (e) => {
    if (!isDraggingSpine.value || !originalWrapperRef.value || !e.touches[0]) return
    const rect = originalWrapperRef.value.getBoundingClientRect()
    let x = (e.touches[0].clientX - rect.left) / rect.width
    spineX.value = Math.max(0.15, Math.min(0.85, x))
}

const stopSpineDrag = () => {
    isDraggingSpine.value = false
    window.removeEventListener('mousemove', onSpineMouseMove)
    window.removeEventListener('touchmove', onSpineTouchMove)
    window.removeEventListener('mouseup', stopSpineDrag)
    window.removeEventListener('touchend', stopSpineDrag)

    // Constrain corners
    if (corners.value.tl.x > spineX.value) corners.value.tl.x = spineX.value
    if (corners.value.tr.x > spineX.value) corners.value.tr.x = spineX.value
    if (corners.value.br.x > spineX.value) corners.value.br.x = spineX.value
    if (corners.value.bl.x > spineX.value) corners.value.bl.x = spineX.value

    if (cornersRight.value.tl.x < spineX.value) cornersRight.value.tl.x = spineX.value
    if (cornersRight.value.tr.x < spineX.value) cornersRight.value.tr.x = spineX.value
    if (cornersRight.value.br.x < spineX.value) cornersRight.value.br.x = spineX.value
    if (cornersRight.value.bl.x < spineX.value) cornersRight.value.bl.x = spineX.value

    processAndCompress()
}

// -------------------------------------------------------------
// CAMERA SCANNER OVERLAY
// -------------------------------------------------------------
const startCamera = async () => {
    showCameraModal.value = true
    cameraStatusText.value = 'Mengakses Kamera...'
    stableFrameCount = 0
    prevCamPoints = null

    try {
        cameraStream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } }
        })
        await nextTick()
        if (cameraVideoEl.value) {
            cameraVideoEl.value.srcObject = cameraStream.value
            cameraVideoEl.value.onloadedmetadata = () => {
                cameraVideoEl.value.play()
                runCameraFrameLoop()
            }
        }
    } catch (err) {
        console.error('Camera error:', err)
        showToast('Gagal membuka kamera: ' + err.message, 'error')
        showCameraModal.value = false
    }
}

const stopCamera = () => {
    if (cameraProcessRaf) cancelAnimationFrame(cameraProcessRaf)
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach(t => t.stop())
        cameraStream.value = null
    }
    showCameraModal.value = false
}

const runCameraFrameLoop = () => {
    if (!showCameraModal.value || !cameraVideoEl.value || !cameraOverlayCanvas.value) return

    const video = cameraVideoEl.value
    const canvas = cameraOverlayCanvas.value
    if (video.videoWidth === 0) {
        cameraProcessRaf = requestAnimationFrame(runCameraFrameLoop)
        return
    }

    if (canvas.width !== video.clientWidth || canvas.height !== video.clientHeight) {
        canvas.width = video.clientWidth
        canvas.height = video.clientHeight
    }

    const ctx = canvas.getContext('2d')
    ctx.clearRect(0, 0, canvas.width, canvas.height)

    // Draw Guide Box
    ctx.strokeStyle = 'rgba(37, 99, 235, 0.7)'
    ctx.lineWidth = 3
    ctx.setLineDash([8, 8])
    const padX = canvas.width * 0.1
    const padY = canvas.height * 0.1
    ctx.strokeRect(padX, padY, canvas.width - padX * 2, canvas.height - padY * 2)
    ctx.setLineDash([])

    cameraStatusText.value = 'Arahkan kamera ke dokumen dan tahan stabil'
    cameraProcessRaf = requestAnimationFrame(runCameraFrameLoop)
}

const captureCameraPhoto = () => {
    if (!cameraVideoEl.value) return
    const video = cameraVideoEl.value

    const tempCanvas = document.createElement('canvas')
    tempCanvas.width = video.videoWidth || 1920
    tempCanvas.height = video.videoHeight || 1080
    const ctx = tempCanvas.getContext('2d')
    ctx.drawImage(video, 0, 0)

    originalFileSize.value = Math.round(tempCanvas.width * tempCanvas.height * 0.15)
    originalImageDimensions.value = { width: tempCanvas.width, height: tempCanvas.height }

    if (originalCanvasRef.value) {
        originalCanvasRef.value.width = tempCanvas.width
        originalCanvasRef.value.height = tempCanvas.height
        const oCtx = originalCanvasRef.value.getContext('2d')
        oCtx.drawImage(tempCanvas, 0, 0)

        if (originalOpenCvMat) originalOpenCvMat.delete()
        originalOpenCvMat = window.cv.imread(originalCanvasRef.value)

        stopCamera()
        fitOriginalCanvas(tempCanvas.width, tempCanvas.height)
        runSpineDetection(originalOpenCvMat)
        detectCorners(originalOpenCvMat)
        processAndCompress()
        showToast('Foto berhasil diambil dari kamera!', 'success')
    }
}

// -------------------------------------------------------------
// DOWNLOAD & BATCH PDF GENERATION
// -------------------------------------------------------------
const downloadImage = (type = 'single') => {
    let blob = singleResultBlob
    let filename = `scan_${Date.now()}.jpg`
    if (type === 'left') {
        blob = leftResultBlob
        filename = `scan_hal1_kiri_${Date.now()}.jpg`
    } else if (type === 'right') {
        blob = rightResultBlob
        filename = `scan_hal2_kanan_${Date.now()}.jpg`
    }

    if (!blob) {
        showToast('Hasil pindaian belum siap untuk diunduh.', 'error')
        return
    }

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 2000)
    showToast('Unduhan berkas JPG berhasil dimulai.', 'success')
}

const addCurrentPageToQueue = () => {
    if (!outputCanvasRef.value && (!outputLeftCanvasRef.value || !outputRightCanvasRef.value)) {
        showToast('Silakan pindai dokumen terlebih dahulu.', 'error')
        return
    }

    if (!isBookMode.value) {
        const dataUrl = outputCanvasRef.value.toDataURL('image/jpeg', jpegQuality.value)
        pdfPageQueue.value.push({
            id: Date.now() + Math.random().toString(36).substring(2, 9),
            filename: `Halaman_${pdfPageQueue.value.length + 1}`,
            canvasDataURL: dataUrl,
            blobSize: singleResultBlob ? singleResultBlob.size : 150000
        })
    } else {
        const dataUrlL = outputLeftCanvasRef.value.toDataURL('image/jpeg', jpegQuality.value)
        const dataUrlR = outputRightCanvasRef.value.toDataURL('image/jpeg', jpegQuality.value)

        pdfPageQueue.value.push({
            id: Date.now() + '_L',
            filename: `Halaman_${pdfPageQueue.value.length + 1}_Kiri`,
            canvasDataURL: dataUrlL,
            blobSize: leftResultBlob ? leftResultBlob.size : 150000
        })
        pdfPageQueue.value.push({
            id: Date.now() + '_R',
            filename: `Halaman_${pdfPageQueue.value.length + 2}_Kanan`,
            canvasDataURL: dataUrlR,
            blobSize: rightResultBlob ? rightResultBlob.size : 150000
        })
    }

    showToast(`Halaman berhasil disimpan ke antrean PDF! (${pdfPageQueue.value.length} total)`, 'success')
}

const removeQueueItem = (index) => {
    pdfPageQueue.value.splice(index, 1)
    showToast('Halaman dihapus dari antrean.', 'info')
}

const clearQueue = () => {
    pdfPageQueue.value = []
    showToast('Antrean halaman PDF telah dikosongkan.', 'info')
}

const generatePdf = async () => {
    if (pdfPageQueue.value.length === 0) {
        // If queue empty, add active scan first
        if (outputCanvasRef.value) {
            addCurrentPageToQueue()
        } else {
            showToast('Tidak ada halaman dalam antrean untuk diekspor ke PDF.', 'error')
            return
        }
    }

    if (!window.jspdf || !window.jspdf.jsPDF) {
        showToast('Pustaka jsPDF belum siap. Mohon tunggu...', 'error')
        return
    }

    isGeneratingPdf.value = true
    showToast('Menghasilkan berkas PDF kompresi tinggi...', 'info')

    try {
        const { jsPDF } = window.jspdf
        const doc = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
            compress: true
        })

        const pages = pdfPageQueue.value
        for (let i = 0; i < pages.length; i++) {
            if (i > 0) doc.addPage('a4', 'portrait')
            doc.addImage(pages[i].canvasDataURL, 'JPEG', 0, 0, 210, 297, undefined, 'FAST')
        }

        const safeFilename = (pdfFilename.value.trim() || 'Dokumen_Scan_SINTA') + '.pdf'
        doc.save(safeFilename)
        showToast(`Berkas PDF "${safeFilename}" berhasil diunduh!`, 'success')
    } catch (err) {
        console.error('PDF error:', err)
        showToast('Gagal membuat PDF: ' + err.message, 'error')
    } finally {
        isGeneratingPdf.value = false
    }
}

// -------------------------------------------------------------
// TESSERACT.JS OCR LOGIC
// -------------------------------------------------------------
const runOcr = async () => {
    if (!outputCanvasRef.value && !outputLeftCanvasRef.value) {
        showToast('Silakan pindai dokumen terlebih dahulu sebelum menjalankan OCR.', 'error')
        return
    }

    try {
        await loadExternalScript('https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js', 'tesseract-script')
    } catch (e) {
        showToast('Gagal memuat pustaka Tesseract OCR.', 'error')
        return
    }

    if (!window.Tesseract) {
        showToast('Tesseract.js tidak tersedia.', 'error')
        return
    }

    isOcrRunning.value = true
    ocrProgressPercent.value = 0
    ocrProgressStatus.value = 'Mengunduh model bahasa & menganalisis teks...'

    try {
        const canvasToOcr = outputCanvasRef.value || outputLeftCanvasRef.value
        const result = await window.Tesseract.recognize(
            canvasToOcr,
            ocrLanguage.value,
            {
                logger: (m) => {
                    if (m.status === 'recognizing text') {
                        ocrProgressPercent.value = Math.round(m.progress * 100)
                        ocrProgressStatus.value = `Mengekstrak teks... ${ocrProgressPercent.value}%`
                    } else {
                        ocrProgressStatus.value = m.status
                    }
                }
            }
        )

        ocrResultText.value = result.data.text
        showToast('Ekstraksi teks (OCR) selesai!', 'success')
    } catch (err) {
        console.error('OCR Error:', err)
        showToast('Gagal mengekstrak teks OCR: ' + err.message, 'error')
    } finally {
        isOcrRunning.value = false
    }
}

const copyOcrText = () => {
    if (!ocrResultText.value) return
    navigator.clipboard.writeText(ocrResultText.value)
    showToast('Teks hasil OCR berhasil disalin ke clipboard!', 'success')
}

const downloadOcrText = () => {
    if (!ocrResultText.value) return
    const blob = new Blob([ocrResultText.value], { type: 'text/plain;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `OCR_Text_${Date.now()}.txt`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 2000)
    showToast('Berkas .txt berhasil diunduh.', 'success')
}

// SVG Polygon points helper
const overlayPointsLeft = computed(() => {
    return `${corners.value.tl.x * 100}%,${corners.value.tl.y * 100}% ` +
           `${corners.value.tr.x * 100}%,${corners.value.tr.y * 100}% ` +
           `${corners.value.br.x * 100}%,${corners.value.br.y * 100}% ` +
           `${corners.value.bl.x * 100}%,${corners.value.bl.y * 100}%`
})

const overlayPointsRight = computed(() => {
    return `${cornersRight.value.tl.x * 100}%,${cornersRight.value.tl.y * 100}% ` +
           `${cornersRight.value.tr.x * 100}%,${cornersRight.value.tr.y * 100}% ` +
           `${cornersRight.value.br.x * 100}%,${cornersRight.value.br.y * 100}% ` +
           `${cornersRight.value.bl.x * 100}%,${cornersRight.value.bl.y * 100}%`
})
</script>

<template>
    <AppLayout title="Pemindai & Kompresor Dokumen">
        <Head title="AeroScan - Pemindai Dokumen" />

        <!-- Floating Toast Messages -->
        <div class="fixed bottom-4 right-4 z-[99999] flex flex-col gap-2 pointer-events-none max-w-sm w-full">
            <div 
                v-for="toast in toastList" 
                :key="toast.id" 
                class="p-3.5 rounded-2xl shadow-xl border text-xs font-semibold flex items-center gap-2.5 transition-all duration-300 pointer-events-auto animate-in slide-in-from-right"
                :class="{
                    'bg-white text-slate-800 border-slate-200 border-l-4 border-l-blue-600': toast.type === 'info',
                    'bg-white text-emerald-900 border-emerald-200 border-l-4 border-l-emerald-600': toast.type === 'success',
                    'bg-white text-rose-900 border-rose-200 border-l-4 border-l-rose-600': toast.type === 'error'
                }"
            >
                <i class="bi text-base" :class="{
                    'bi-info-circle-fill text-blue-600': toast.type === 'info',
                    'bi-check-circle-fill text-emerald-600': toast.type === 'success',
                    'bi-exclamation-triangle-fill text-rose-600': toast.type === 'error'
                }"></i>
                <span class="grow leading-relaxed">{{ toast.message }}</span>
            </div>
        </div>

        <!-- Floating Magnifier Lens -->
        <div 
            v-show="isMagnifierActive" 
            class="fixed w-28 h-28 rounded-full border-4 border-blue-600 bg-white shadow-2xl z-[9999] pointer-events-none overflow-hidden -translate-x-1/2 -translate-y-full mb-6"
            :style="{ left: magnifierPos.x + 'px', top: magnifierPos.y + 'px' }"
        >
            <canvas ref="magnifierCanvasRef" width="120" height="120" class="w-full h-full"></canvas>
        </div>

        <!-- Hidden File Input -->
        <input 
            type="file" 
            ref="fileInputRef" 
            @change="handleFileUpload" 
            accept="image/*" 
            multiple 
            class="hidden" 
        />

        <div class="space-y-6">

            <!-- 1. Header Toolbar Card -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">AeroScan - Pemindai & Kompresor Dokumen</h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                100% Client-Side Privacy
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Luruskan perspektif foto dokumen, tingkatkan keterbacaan, dan kompres ukuran PDF secara instan.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-mono font-bold">
                        <span class="w-2.5 h-2.5 rounded-full" :class="{
                            'bg-emerald-500 animate-pulse': statusType === 'ready',
                            'bg-amber-500 animate-spin': statusType === 'processing',
                            'bg-rose-500': statusType === 'error'
                        }"></span>
                        <span class="text-slate-700">{{ statusText }}</span>
                    </div>

                    <button 
                        type="button" 
                        @click="loadDemoDocument" 
                        class="h-9 px-3.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        title="Muat Contoh Dokumen Simulasi"
                    >
                        <i class="bi bi-stars"></i>
                        <span>Contoh Dokumen</span>
                    </button>
                </div>
            </div>

            <!-- 2. Standard 3-Way Horizontal NavTabs (Modern Pill Layout) -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
                <div class="flex items-center relative">
                    <!-- Tombol Panah Kiri -->
                    <button 
                        type="button" 
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5 cursor-pointer" 
                        onclick="document.getElementById('scannerTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                        title="Geser ke Kiri"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <!-- Container Deretan Tab -->
                    <div class="nav-tabs-wrapper grow overflow-hidden relative">
                        <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="scannerTabs" role="tablist">
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="!isBookMode && activeTab === 'editor' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="isBookMode = false; activeTab = 'editor'; resetCorners()"
                                >
                                    <i class="bi bi-file-earmark-text-fill me-2 text-sm"></i> Halaman Tunggal
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="isBookMode && activeTab === 'editor' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="isBookMode = true; activeTab = 'editor'; resetCorners()"
                                >
                                    <i class="bi bi-book-half me-2 text-sm"></i> Mode Buku (2 Halaman)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="activeTab === 'ocr' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'ocr'; isOcrActive = true"
                                >
                                    <i class="bi bi-translate me-2 text-sm"></i> Ekstraksi Teks (OCR)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button 
                                    class="border-0 font-semibold px-3.5 py-2 rounded-xl text-xs transition flex items-center cursor-pointer" 
                                    :class="activeTab === 'queue' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                                    @click="activeTab = 'queue'"
                                >
                                    <i class="bi bi-collection-fill me-2 text-sm"></i> Antrean PDF ({{ pdfPageQueue.length }})
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Panah Kanan -->
                    <button 
                        type="button" 
                        class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px] z-5 cursor-pointer" 
                        onclick="document.getElementById('scannerTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                        title="Geser ke Kanan"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- 3. Main Workspace Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- KIRI: Control Sidebar (4 Columns) -->
                <div class="lg:col-span-4 space-y-4">

                    <!-- Sumber Gambar Card -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-3.5">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <i class="bi bi-cloud-arrow-up-fill text-blue-600 text-sm"></i> Sumber Dokumen
                        </h3>

                        <!-- Drag & Drop Zone -->
                        <div 
                            @click="triggerFileInput" 
                            class="border-2 border-dashed border-slate-200 hover:border-blue-500 bg-slate-50/70 hover:bg-blue-50/40 rounded-2xl p-6 text-center transition cursor-pointer flex flex-col items-center justify-center gap-2 group"
                        >
                            <div class="w-12 h-12 rounded-2xl bg-white shadow-xs border border-slate-200/80 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-110 transition">
                                <i class="bi bi-images"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-700">Klik atau Tarik Foto ke Sini</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 font-mono">JPG, PNG (Maks 50MB / Batch)</div>
                            </div>
                        </div>

                        <!-- Camera Action Button -->
                        <button 
                            type="button" 
                            @click="startCamera" 
                            class="w-full h-10 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="bi bi-camera-fill text-sm"></i>
                            <span>Ambil Foto via Kamera</span>
                        </button>
                    </div>

                    <!-- Koreksi & Kualitas Card -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <i class="bi bi-sliders text-indigo-600 text-sm"></i> Koreksi & Filter Kualitas
                        </h3>

                        <!-- Filter Selector (Magic, Warna, Gray, B&W) -->
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Filter Warna</label>
                            <div class="grid grid-cols-4 gap-1.5 p-1 bg-slate-100/80 rounded-xl border border-slate-200">
                                <button 
                                    v-for="f in [{ id: 'magic', label: 'Magic' }, { id: 'color', label: 'Warna' }, { id: 'gray', label: 'Abu' }, { id: 'bw', label: 'B&W' }]" 
                                    :key="f.id"
                                    type="button"
                                    @click="activeFilter = f.id; processAndCompress()"
                                    class="py-1.5 rounded-lg text-xs font-bold transition cursor-pointer text-center"
                                    :class="activeFilter === f.id ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                                >
                                    {{ f.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Resolution Preset -->
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Preset Resolusi Output</label>
                            <select 
                                v-model="resolutionPreset" 
                                @change="processAndCompress()"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition"
                            >
                                <option value="standard">Standar (1200 x 1842 px - A4 Ringan)</option>
                                <option value="hd">High-Definition HD (1600 x 2456 px)</option>
                                <option value="super-hd">Super-HD Ultra (2400 x 3684 px - Tajam Maksimal)</option>
                            </select>
                        </div>

                        <!-- JPEG Quality Slider -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 font-medium">Kualitas JPEG</span>
                                <strong class="font-mono text-blue-600 font-bold">{{ Math.round(jpegQuality * 100) }}%</strong>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="jpegQuality" 
                                @input="processAndCompress(true)"
                                @change="processAndCompress(false)"
                                min="0.1" max="1.0" step="0.05" 
                                class="w-full accent-blue-600 cursor-pointer"
                            />
                        </div>

                        <!-- USM Sharpen Slider -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 font-medium">Penajaman Teks (Sharpen)</span>
                                <strong class="font-mono text-indigo-600 font-bold">{{ sharpenVal.toFixed(1) }}</strong>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="sharpenVal" 
                                @input="processAndCompress(true)"
                                @change="processAndCompress(false)"
                                min="0.0" max="2.0" step="0.1" 
                                class="w-full accent-indigo-600 cursor-pointer"
                            />
                        </div>

                        <!-- Brightness Slider -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 font-medium">Kecerahan (Brightness)</span>
                                <strong class="font-mono text-slate-700 font-bold">{{ brightnessVal }}</strong>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="brightnessVal" 
                                @input="processAndCompress(true)"
                                @change="processAndCompress(false)"
                                min="-50" max="50" step="5" 
                                class="w-full accent-slate-600 cursor-pointer"
                            />
                        </div>

                        <!-- Contrast Slider -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 font-medium">Kontras Kertas</span>
                                <strong class="font-mono text-slate-700 font-bold">{{ contrastVal.toFixed(2) }}x</strong>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="contrastVal" 
                                @input="processAndCompress(true)"
                                @change="processAndCompress(false)"
                                min="0.6" max="2.0" step="0.05" 
                                class="w-full accent-slate-600 cursor-pointer"
                            />
                        </div>

                        <!-- Action Toolbar -->
                        <div class="pt-2 border-t border-slate-100 flex gap-2">
                            <button 
                                type="button" 
                                @click="rotateImage"
                                class="flex-1 h-8 px-3 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                                title="Putar Gambar 90 Derajat"
                            >
                                <i class="bi bi-arrow-clockwise"></i>
                                <span>Putar 90°</span>
                            </button>
                            <button 
                                type="button" 
                                @click="resetCorners"
                                class="flex-1 h-8 px-3 border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                                title="Kembalikan Posisi Sudut Dokumen"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset Sudut</span>
                            </button>
                        </div>

                    </div>

                    <!-- Ekspor PDF & Batch Card -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-3.5">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <i class="bi bi-file-earmark-pdf-fill text-rose-600 text-sm"></i> Ekspor Dokumen PDF
                        </h3>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nama Berkas PDF</label>
                            <input 
                                type="text" 
                                v-model="pdfFilename" 
                                placeholder="Dokumen_Scan_SINTA"
                                class="w-full h-9 px-3 rounded-xl border border-slate-200 text-xs font-mono text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <button 
                                type="button" 
                                @click="addCurrentPageToQueue" 
                                class="w-full h-9 px-3 border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                            >
                                <i class="bi bi-plus-circle-fill"></i>
                                <span>Simpan Halaman ke Antrean PDF</span>
                            </button>

                            <button 
                                type="button" 
                                @click="generatePdf" 
                                :disabled="isGeneratingPdf"
                                class="w-full h-10 px-4 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
                            >
                                <i v-if="!isGeneratingPdf" class="bi bi-file-earmark-arrow-down-fill text-base"></i>
                                <i v-else class="bi bi-arrow-repeat animate-spin text-base"></i>
                                <span>Download PDF ({{ pdfPageQueue.length || 1 }} Halaman)</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- KANAN: Workspace Canvas & Results (8 Columns) -->
                <div class="lg:col-span-8 space-y-4">

                    <!-- Panel 1: Original Canvas & Interactive Corner Detection -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-3">
                        
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Foto Dokumen Asli & Deteksi Sudut</h3>
                            </div>
                            <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                {{ originalImageDimensions.width }} × {{ originalImageDimensions.height }} px
                            </span>
                        </div>

                        <!-- Interactive Canvas Container -->
                        <div 
                            id="original-canvas-container" 
                            class="relative bg-slate-900/90 rounded-2xl overflow-hidden flex items-center justify-center min-h-[380px] max-h-[500px] border border-slate-800"
                        >
                            <!-- Empty State -->
                            <div v-if="originalImageDimensions.width === 0" class="text-center p-8 space-y-2 text-slate-400">
                                <i class="bi bi-file-earmark-image text-5xl text-slate-600 block mb-1"></i>
                                <div class="text-sm font-bold text-slate-300">Belum Ada Dokumen yang Dipilih</div>
                                <p class="text-xs text-slate-500 max-w-sm">Unggah foto dokumen atau klik "Contoh Dokumen" di atas untuk memulai pemindaian otomatis.</p>
                            </div>

                            <!-- Interactive Wrapper -->
                            <div 
                                v-show="originalImageDimensions.width > 0" 
                                ref="originalWrapperRef" 
                                class="relative inline-block select-none"
                            >
                                <canvas ref="originalCanvasRef" class="block max-w-full max-h-full object-contain"></canvas>

                                <!-- SVG Polygon Overlay -->
                                <svg class="absolute inset-0 w-full h-full pointer-events-none z-10">
                                    <!-- Left / Single Page Polygon (Cyan) -->
                                    <polygon 
                                        :points="overlayPointsLeft" 
                                        stroke="#2563eb" 
                                        stroke-width="2.5" 
                                        fill="rgba(37,99,235,0.15)"
                                    ></polygon>
                                    <!-- Right Page Polygon (Magenta) in Book Mode -->
                                    <polygon 
                                        v-if="isBookMode"
                                        :points="overlayPointsRight" 
                                        stroke="#db2777" 
                                        stroke-width="2.5" 
                                        fill="rgba(219,39,119,0.15)"
                                    ></polygon>
                                </svg>

                                <!-- Spine Divider Line (Book / Fold) -->
                                <div 
                                    v-if="isSpineActive || isBookMode"
                                    class="absolute top-0 bottom-0 w-1.5 bg-amber-400 cursor-ew-resize z-20 -translate-x-1/2 shadow-lg border-l border-r border-amber-200"
                                    :style="{ left: (spineX * 100) + '%' }"
                                    @mousedown="startSpineDrag"
                                    @touchstart="startSpineDrag"
                                    title="Tarik untuk menggeser garis pembatas buku"
                                >
                                    <div class="absolute top-2 left-1/2 -translate-x-1/2 bg-amber-500 text-slate-950 font-black text-[9px] px-1.5 py-0.5 rounded shadow pointer-events-none whitespace-nowrap">
                                        SPINE
                                    </div>
                                </div>

                                <!-- Left / Single 4 Corner Drag Handles (Cyan) -->
                                <div 
                                    v-for="(pos, key) in corners" 
                                    :key="key"
                                    class="absolute w-5 h-5 rounded-full bg-blue-600 border-2 border-white shadow-lg cursor-grab active:cursor-grabbing -translate-x-1/2 -translate-y-1/2 z-30 hover:scale-125 transition-transform"
                                    :style="{ left: (pos.x * 100) + '%', top: (pos.y * 100) + '%' }"
                                    @mousedown="startCornerDrag(key, $event)"
                                    @touchstart="startCornerDrag(key, $event)"
                                ></div>

                                <!-- Right 4 Corner Drag Handles (Magenta - Book Mode) -->
                                <template v-if="isBookMode">
                                    <div 
                                        v-for="(pos, key) in cornersRight" 
                                        :key="'r' + key"
                                        class="absolute w-5 h-5 rounded-full bg-pink-600 border-2 border-white shadow-lg cursor-grab active:cursor-grabbing -translate-x-1/2 -translate-y-1/2 z-30 hover:scale-125 transition-transform"
                                        :style="{ left: (pos.x * 100) + '%', top: (pos.y * 100) + '%' }"
                                        @mousedown="startCornerDrag('r' + key, $event)"
                                        @touchstart="startCornerDrag('r' + key, $event)"
                                    ></div>
                                </template>

                            </div>

                        </div>

                        <div class="p-3 bg-blue-50/70 text-blue-800 rounded-xl text-xs flex items-start gap-2 border border-blue-100">
                            <i class="bi bi-info-circle-fill text-blue-600 shrink-0 mt-0.5"></i>
                            <span>
                                <strong>Tips Penyesuaian:</strong> Geser titik penanda lingkaran biru (atau pink pada mode buku) pada sudut kertas di atas jika hasil potong otomatis kurang presisi.
                            </span>
                        </div>

                    </div>

                    <!-- Panel 2: Result Preview & Compression Stats -->
                    <div v-show="activeTab !== 'queue' && activeTab !== 'ocr'" class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
                        
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Hasil Pemindaian Terkompresi</h3>
                            </div>
                            <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ outputDimensions.width }} × {{ outputDimensions.height }} px
                            </span>
                        </div>

                        <!-- Result Output Canvas Display -->
                        <div class="bg-slate-100/70 rounded-2xl p-3 border border-slate-200 min-h-[350px] flex items-center justify-center">
                            
                            <!-- Single Output View -->
                            <div v-show="!isBookMode" class="w-full flex items-center justify-center">
                                <canvas ref="outputCanvasRef" class="max-h-[420px] max-w-full rounded-xl shadow-md border border-slate-200"></canvas>
                            </div>

                            <!-- Dual Book Output View -->
                            <div v-show="isBookMode" class="w-full grid grid-cols-2 gap-3">
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Halaman Kiri (1)</span>
                                    <canvas ref="outputLeftCanvasRef" class="max-h-[380px] max-w-full rounded-xl shadow-md border border-slate-200"></canvas>
                                </div>
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Halaman Kanan (2)</span>
                                    <canvas ref="outputRightCanvasRef" class="max-h-[380px] max-w-full rounded-xl shadow-md border border-slate-200"></canvas>
                                </div>
                            </div>

                        </div>

                        <!-- Downloader & Compression KPI Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            
                            <!-- Download Buttons -->
                            <div class="flex items-center gap-2">
                                <template v-if="!isBookMode">
                                    <button 
                                        type="button" 
                                        @click="downloadImage('single')" 
                                        class="w-full h-10 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <i class="bi bi-download"></i>
                                        <span>Unduh Berkas JPG</span>
                                    </button>
                                </template>
                                <template v-else>
                                    <button 
                                        type="button" 
                                        @click="downloadImage('left')" 
                                        class="flex-1 h-10 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-1 cursor-pointer"
                                    >
                                        <i class="bi bi-download"></i> Hal Kiri
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="downloadImage('right')" 
                                        class="flex-1 h-10 px-3 bg-pink-600 hover:bg-pink-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-1 cursor-pointer"
                                    >
                                        <i class="bi bi-download"></i> Hal Kanan
                                    </button>
                                </template>
                            </div>

                            <!-- Compression KPI Boxes -->
                            <div class="grid grid-cols-3 gap-2">
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-center font-mono">
                                    <div class="text-[10px] text-slate-400 uppercase font-sans">Ukuran Asli</div>
                                    <div class="text-xs font-bold text-slate-700 mt-0.5">{{ originalSizeFormatted }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-center font-mono">
                                    <div class="text-[10px] text-slate-400 uppercase font-sans">Ukuran Hasil</div>
                                    <div class="text-xs font-bold text-slate-800 mt-0.5">{{ compressedSizeFormatted }}</div>
                                </div>
                                <div class="p-2 bg-emerald-50 border border-emerald-200 rounded-xl text-center font-mono">
                                    <div class="text-[10px] text-emerald-600 uppercase font-sans font-bold">Penghematan</div>
                                    <div class="text-xs font-black text-emerald-700 mt-0.5">{{ savingsRatio }}</div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Panel 3: OCR Text Extraction Panel -->
                    <div v-show="activeTab === 'ocr'" class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-translate text-indigo-600 text-base"></i>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Ekstraksi Teks Otomatis (OCR)</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <select 
                                    v-model="ocrLanguage" 
                                    class="h-8 px-2.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 bg-white focus:outline-none"
                                >
                                    <option value="ind">Bahasa Indonesia (ind)</option>
                                    <option value="eng">Bahasa Inggris (eng)</option>
                                </select>
                                <button 
                                    type="button" 
                                    @click="runOcr" 
                                    :disabled="isOcrRunning"
                                    class="h-8 px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer shadow-2xs"
                                >
                                    <i v-if="!isOcrRunning" class="bi bi-play-fill"></i>
                                    <i v-else class="bi bi-arrow-repeat animate-spin"></i>
                                    <span>{{ isOcrRunning ? 'Memproses...' : 'Jalankan OCR' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- OCR Progress Bar -->
                        <div v-if="isOcrRunning" class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl space-y-1.5">
                            <div class="flex justify-between text-xs font-mono text-indigo-900">
                                <span>{{ ocrProgressStatus }}</span>
                                <strong>{{ ocrProgressPercent }}%</strong>
                            </div>
                            <div class="w-full bg-indigo-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-600 h-full transition-all duration-300" :style="{ width: ocrProgressPercent + '%' }"></div>
                            </div>
                        </div>

                        <!-- OCR Text Editor -->
                        <div>
                            <textarea 
                                v-model="ocrResultText" 
                                rows="10" 
                                placeholder="Teks hasil ekstraksi OCR akan muncul di sini. Anda dapat langsung mengedit dan menyalin teks ini..."
                                class="w-full p-3.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 leading-relaxed focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition resize-none bg-slate-50/50"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <button 
                                type="button" 
                                @click="copyOcrText" 
                                :disabled="!ocrResultText"
                                class="h-9 px-3.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5 disabled:opacity-40 cursor-pointer"
                            >
                                <i class="bi bi-clipboard-check"></i> Salin Teks
                            </button>
                            <button 
                                type="button" 
                                @click="downloadOcrText" 
                                :disabled="!ocrResultText"
                                class="h-9 px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 disabled:opacity-40 cursor-pointer"
                            >
                                <i class="bi bi-download"></i> Unduh .txt
                            </button>
                        </div>
                    </div>

                    <!-- Panel 4: Batch Queue List -->
                    <div v-show="activeTab === 'queue'" class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-collection-fill text-blue-600 text-base"></i>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Antrean Halaman PDF ({{ pdfPageQueue.length }})</h3>
                            </div>
                            <button 
                                v-if="pdfPageQueue.length > 0"
                                type="button" 
                                @click="clearQueue" 
                                class="h-7 px-2.5 text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 transition cursor-pointer"
                            >
                                Kosongkan Antrean
                            </button>
                        </div>

                        <!-- Thumbnails Grid -->
                        <div v-if="pdfPageQueue.length === 0" class="text-center py-12 text-slate-400 space-y-2">
                            <i class="bi bi-folder-x text-4xl text-slate-300 block mb-1"></i>
                            <div class="text-xs font-bold">Antrean PDF Masih Kosong</div>
                            <p class="text-[11px] text-slate-400 max-w-sm mx-auto">Pindai dokumen lalu klik "Simpan Halaman ke Antrean PDF" atau unggah banyak berkas sekaligus.</p>
                        </div>

                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <div 
                                v-for="(item, idx) in pdfPageQueue" 
                                :key="item.id" 
                                class="bg-slate-50 rounded-xl border border-slate-200 p-2 relative group hover:shadow-md transition"
                            >
                                <div class="aspect-[3/4] bg-white rounded-lg overflow-hidden border border-slate-200 flex items-center justify-center">
                                    <img :src="item.canvasDataURL" :alt="item.filename" class="w-full h-full object-contain" />
                                </div>
                                <div class="mt-2 flex items-center justify-between text-[11px]">
                                    <span class="font-bold text-slate-700 font-mono truncate max-w-[90px]">Hal {{ idx + 1 }}</span>
                                    <button 
                                        type="button" 
                                        @click="removeQueueItem(idx)" 
                                        class="text-rose-500 hover:text-rose-700 transition cursor-pointer p-1"
                                        title="Hapus dari antrean"
                                    >
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- CAMERA SCANNER MODAL OVERLAY -->
        <div v-if="showCameraModal" class="fixed inset-0 z-[10000] bg-black/90 backdrop-blur-md flex flex-col items-center justify-between p-4 sm:p-6">
            
            <!-- Top Status Bar -->
            <div class="w-full max-w-md bg-slate-900/80 border border-slate-700 rounded-full py-2.5 px-6 text-center text-white text-xs font-bold shadow-2xl backdrop-blur-sm">
                {{ cameraStatusText }}
            </div>

            <!-- Video & Canvas Viewport -->
            <div class="relative w-full max-w-2xl flex-1 max-h-[70vh] flex items-center justify-center overflow-hidden rounded-3xl border border-slate-800 bg-black my-4">
                <video ref="cameraVideoEl" class="w-full h-full object-cover" autoplay playsinline></video>
                <canvas ref="cameraOverlayCanvas" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
            </div>

            <!-- Bottom Controls -->
            <div class="flex items-center gap-6">
                <button 
                    type="button" 
                    @click="stopCamera" 
                    class="h-12 px-6 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition border border-slate-600 shadow-xl cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="button" 
                    @click="captureCameraPhoto" 
                    class="h-14 px-8 rounded-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm transition shadow-2xl flex items-center gap-2 cursor-pointer border-2 border-white/20"
                >
                    <i class="bi bi-camera-fill text-lg"></i>
                    <span>Ambil Foto</span>
                </button>
            </div>

        </div>

    </AppLayout>
</template>
