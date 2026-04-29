/**
 * UI Module Main Entry Point
 */

import Cropper from 'cropperjs'
window.Cropper = Cropper

document.addEventListener('alpine:init', () => {
    const getAlpineData = (element) => {
        if (!element) {
            return null
        }

        if (window.Alpine?.$data) {
            return window.Alpine.$data(element)
        }

        return element._x_dataStack?.[0] ?? null
    }

    const getChoicesState = (wrapper) => {
        const states = [...wrapper.querySelectorAll('[x-data]')]
            .map((element) => getAlpineData(element))
            .filter(Boolean)

        return {
            visual: states.find((state) => typeof state.focused !== 'undefined') ?? null,
            controller: states.find((state) => typeof state.clear === 'function') ?? null,
        }
    }

    const getChoicesTriggerContext = (event) => {
        const wrapper = event.target.closest('[data-ui-choices]')
        const trigger = event.target.closest('label.select')
        const optionsPanel = event.target.closest("[wire\\:key^='options-list-']")
        const interactiveIcon = event.target.closest('svg, button, a')

        if (!wrapper || !trigger || optionsPanel || interactiveIcon) {
            return null
        }

        return { wrapper }
    }

    if (!window.__internaraChoicesToggleBound) {
        document.addEventListener(
            'pointerdown',
            (event) => {
                const context = getChoicesTriggerContext(event)

                if (!context) {
                    return
                }

                const { visual } = getChoicesState(context.wrapper)
                context.wrapper.dataset.choicesWasOpen = String(Boolean(visual?.focused))
            },
            true,
        )

        document.addEventListener('click', (event) => {
            const context = getChoicesTriggerContext(event)

            if (!context) {
                return
            }

            const { wrapper } = context
            const wasOpen = wrapper.dataset.choicesWasOpen === 'true'
            delete wrapper.dataset.choicesWasOpen

            if (!wasOpen) {
                return
            }

            const { visual, controller } = getChoicesState(wrapper)

            if (!visual?.focused) {
                return
            }

            if (typeof controller?.clear === 'function') {
                controller.clear()
            } else {
                visual.focused = false
            }

            wrapper.querySelector('input')?.blur()
        })

        window.__internaraChoicesToggleBound = true
    }

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

                    // Cropper v2 Initialization
                    this.cropper = new window.Cropper(image)

                    // Set initial state after elements are ready
                    this.cropper.getCropperCanvas().$nextTick(() => {
                        const selection = this.cropper.getCropperSelection()
                        if (selection) {
                            selection.aspectRatio = ratioValue
                        }
                    })
                })
            }
            reader.readAsDataURL(file)
        },

        async applyCrop() {
            if (!this.cropper) return

            const selection = this.cropper.getCropperSelection()
            if (!selection) return

            const canvas = await selection.$toCanvas()
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
            if (this.cropper) {
                const cropperImage = this.cropper.getCropperImage()
                if (cropperImage) {
                    // Convert degrees to radians for v2
                    cropperImage.$rotate((deg * Math.PI) / 180)
                }
            }
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
