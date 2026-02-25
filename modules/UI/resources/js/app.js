/**
 * UI Module Main Entry Point
 */

import Cropper from 'cropperjs'
window.Cropper = Cropper

document.addEventListener('alpine:init', () => {
    // Single Cohesive File & Cropper Component
    Alpine.data('fileComponent', (config) => ({
        isDropping: false,
        showCropper: false,
        cropper: null,
        rawFile: null,
        files: [],
        model: config.model,
        ratio: config.ratio || 1,
        isCrop: config.isCrop || false,

        init() {
            if (config.preview) {
                this.files = [
                    {
                        id: 'existing',
                        url: config.preview,
                        name: 'Existing File',
                        type: config.previewType || 'image/jpeg',
                        isNew: false,
                    },
                ]
            }
        },

        handleSelect(event) {
            const selectedFiles = event.target.files
            if (selectedFiles.length > 0) this.processFile(selectedFiles[0])
        },

        handleDrop(event) {
            this.isDropping = false
            const droppedFiles = event.dataTransfer.files
            if (droppedFiles.length > 0) this.processFile(droppedFiles[0])
        },

        processFile(file) {
            const isImage = file.type.startsWith('image/')

            if (this.isCrop && isImage) {
                this.rawFile = file
                this.openCropper(file)
            } else {
                this.syncToLivewire(file)
            }
        },

        openCropper(file) {
            const reader = new FileReader()
            reader.onload = (e) => {
                this.showCropper = true

                this.$nextTick(() => {
                    const image = this.$refs.cropperImage
                    if (!image) return

                    image.src = e.target.result

                    if (this.cropper) this.cropper.destroy()

                    // Parse ratio
                    let ratioValue = 1
                    if (typeof this.ratio === 'string' && this.ratio.includes('/')) {
                        const [w, h] = this.ratio.split('/')
                        ratioValue = parseFloat(w) / parseFloat(h)
                    } else {
                        ratioValue = parseFloat(this.ratio) || 1
                    }

                    this.cropper = new window.Cropper(image, {
                        aspectRatio: ratioValue,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        responsive: true,
                        restore: false,
                        center: true,
                        highlight: true,
                        background: true,
                    })
                })
            }
            reader.readAsDataURL(file)
        },

        applyCrop() {
            if (!this.cropper) return

            const canvas = this.cropper.getCroppedCanvas()
            canvas.toBlob((blob) => {
                const croppedFile = new File([blob], this.rawFile.name, { type: this.rawFile.type })
                this.syncToLivewire(croppedFile)
                this.closeCropper()
            }, this.rawFile.type)
        },

        closeCropper() {
            this.showCropper = false
            if (this.cropper) {
                this.cropper.destroy()
                this.cropper = null
            }
            if (this.$refs.input) {
                this.$refs.input.value = ''
            }
        },

        rotate(deg) {
            if (this.cropper) this.cropper.rotate(deg)
        },

        syncToLivewire(file) {
            this.files = [
                {
                    id: 'new-' + Math.random(),
                    url: URL.createObjectURL(file),
                    name: file.name,
                    type: file.type,
                    isNew: true,
                },
            ]

            if (this.model) {
                this.$wire.upload(this.model, file)
            }
        },

        removeFile() {
            this.files = []
            if (this.$refs.input) this.$refs.input.value = ''
            if (this.model) {
                this.$wire.set(this.model, null)
            }
        },
    }))
})
