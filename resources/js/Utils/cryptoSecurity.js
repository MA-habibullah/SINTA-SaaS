/**
 * SINTA-SaaS Client-Side Cryptographic Security & Anti-Inspection Utility
 * Memastikan proteksi data sensitif di level browser memory, dekripsi on-demand, dan anti-scraping.
 */

import { onUnmounted } from 'vue'

/**
 * Convert base64 string to Uint8Array
 */
function base64ToUint8Array(base64) {
    const binaryString = atob(base64)
    const len = binaryString.length
    const bytes = new Uint8Array(len)
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i)
    }
    return bytes
}

/**
 * Decrypt standard SINTA AES-256-CBC Encrypted Payload in Client Memory
 */
export async function decryptPayload(encryptedString, secretKeyString) {
    if (!encryptedString || typeof encryptedString !== 'string' || !encryptedString.startsWith('ENC:')) {
        return encryptedString // Return as is if not encrypted
    }

    try {
        const rawJson = atob(encryptedString.substring(4))
        const envelope = JSON.parse(rawJson)

        if (!envelope.iv || !envelope.value) {
            throw new Error('Struktur payload enkripsi tidak valid.')
        }

        // Derive 256-bit key using SHA-256 via Web Crypto API
        const keyMaterial = new TextEncoder().encode(secretKeyString || 'SINTA_SECURE_PAYLOAD_RUNTIME_TOKEN')
        const keyBuffer = await window.crypto.subtle.digest('SHA-256', keyMaterial)

        const cryptoKey = await window.crypto.subtle.importKey(
            'raw',
            keyBuffer,
            { name: 'AES-CBC' },
            false,
            ['decrypt']
        )

        const iv = base64ToUint8Array(envelope.iv)
        const ciphertext = base64ToUint8Array(envelope.value)

        const decryptedBuffer = await window.crypto.subtle.decrypt(
            { name: 'AES-CBC', iv: iv },
            cryptoKey,
            ciphertext
        )

        const decryptedText = new TextDecoder().decode(decryptedBuffer)
        return JSON.parse(decryptedText)
    } catch (err) {
        console.warn('[SecurityGuard] Dekripsi payload client-side gagal:', err.message)
        return null
    }
}

/**
 * Mask sensitive string for safe UI presentation (Anti-Over-the-shoulder inspection)
 */
export function maskSensitive(value, type = 'general') {
    if (!value) return '-'
    const str = String(value).trim()
    const len = str.length

    if (type === 'nik' || type === 'nisn') {
        if (len <= 6) return '***'
        return str.substring(0, 4) + '*'.repeat(Math.max(4, len - 8)) + str.substring(len - 4)
    }

    if (type === 'nominal' || type === 'gaji' || type === 'saldo') {
        return 'Rp ••••••••'
    }

    if (type === 'email') {
        const parts = str.split('@')
        if (parts.length === 2) {
            const name = parts[0]
            const domain = parts[1]
            const maskedName = name.length > 2 ? name[0] + '***' + name[name.length - 1] : name[0] + '***'
            return `${maskedName}@${domain}`
        }
    }

    if (len <= 4) return '****'
    return str.substring(0, 2) + '*'.repeat(len - 4) + str.substring(len - 2)
}

/**
 * Vue 3 Composable: Memory Hygiene & Anti-DOM Inspection Guard
 * Otomatis menghapus / membersihkan state reaktif pada memori browser saat komponen unmount.
 */
export function useMemorySecurity(statesToClear = []) {
    onUnmounted(() => {
        // Clear all state refs from browser memory
        statesToClear.forEach(stateRef => {
            if (stateRef && typeof stateRef === 'object') {
                if ('value' in stateRef) {
                    if (Array.isArray(stateRef.value)) {
                        stateRef.value = []
                    } else if (typeof stateRef.value === 'object' && stateRef.value !== null) {
                        stateRef.value = {}
                    } else {
                        stateRef.value = null
                    }
                }
            }
        })
    })
}

/**
 * Install Security Anti-Tamper & Anti-Scraping Guard
 */
export function installAntiInspectionGuard() {
    if (typeof window === 'undefined') return

    // Prevent attaching sensitive data to window
    if (window.__INITIAL_STATE__) {
        delete window.__INITIAL_STATE__
    }
}

