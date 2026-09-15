<template>
    <p v-if="!body || !body.trim()" class="text-muted">No email body available.</p>
    <iframe v-else-if="htmlDocument" title="Email body preview" sandbox="" referrerpolicy="no-referrer"
        :srcdoc="htmlDocument" class="email-preview border rounded" />
    <pre v-else class="email-text border rounded p-3">{{ body }}</pre>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ body: { type: String, default: null } })

const htmlDocument = computed(() => {
    if (!props.body) return null
    // Storage has no MIME type. Recognize email markup, not plain-text comparisons
    // or addresses such as <person@example.com>.
    if (!/<\/?(?:html|head|body|style|title|p|div|span|br|hr|table|thead|tbody|tfoot|tr|td|th|h[1-6]|a|img|b|strong|i|em|u|ul|ol|li|blockquote|pre|code|font|center|script|form|iframe|svg)(?=[\s/>])[^>]*>/i.test(props.body)) return null

    // A template parses inertly. Remove active/navigation content for a static
    // preview; the empty sandbox and CSP remain the security boundary.
    const template = document.createElement('template')
    template.innerHTML = props.body
    template.content.querySelectorAll('script, iframe, frame, frameset, object, embed, meta, base, link, form, input, button, textarea, select, template').forEach(element => element.remove())
    template.content.querySelectorAll('*').forEach(element => {
        for (const attribute of [...element.attributes]) {
            if (/^on/i.test(attribute.name) || /^(?:href|src|srcset|action|formaction|target|xlink:href|background|ping)$/i.test(attribute.name)) {
                element.removeAttribute(attribute.name)
            }
        }
    })

    return `<!doctype html><html><head><meta charset="utf-8">
        <meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'">
        <style>body { margin: 16px; font: 16px/1.5 sans-serif; overflow-wrap: anywhere; } img { max-width: 100%; } pre { white-space: pre-wrap; }</style>
        </head><body>${template.innerHTML}</body></html>`
})
</script>

<style scoped>
.email-preview { width: 100%; height: 28rem; background: white; }
.email-text { white-space: pre-wrap; overflow-wrap: anywhere; }
</style>
