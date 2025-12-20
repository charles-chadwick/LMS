<script>
import { defineComponent, h } from 'vue';
import { Button } from "primevue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";

export const CreateButton = defineComponent ( {
  name: 'CreateButton',
  props: {
    prefix: String,
    message: String,
    query_parameters: {
      type: Object,
      default: null
    }
  },
  setup ( props ) {
    return () => h ( Button, {
      label: `Create ${ props.message }`,
      class: 'text-bold',
      icon: 'pi pi-plus',
      severity: 'primary',
      onClick: () => router.visit ( route ( props.prefix + '.create', props,query_parameters ) )
    } );
  }
} );

export const EditButton = defineComponent ( {
  name: 'EditButton',
  props: {
    prefix: String,
    id: Number
  },
  directives: {
    tooltip: window.vTooltip
  },
  setup ( props ) {
    return () => h ( Button, {
      icon: 'pi pi-pencil',
      severity: 'secondary',
      size: 'small',
      onClick: () => router.visit ( route ( props.prefix + '.edit', props.id ) ),
      'v-tooltip.top': 'Edit'
    } );
  }
} );

export const DeleteButton = defineComponent ( {
  name: 'DeleteButton',
  props: {
    prefix: String,
    id: Number,
    message: String
  },
  setup ( props ) {
    const confirm = useConfirm ();

    const confirmDelete = () => {
      confirm.require ( {
        message: `Are you sure you want to delete ${ props.message }?`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: async () => {

          router.delete ( route ( props.prefix + '.destroy', props.id ) )
          router.reload()

        }
      } );
    };

    return () => h ( Button, {
      icon: 'pi pi-trash',
      severity: 'danger',
      size: 'small',
      'v-tooltip.top': 'Delete',
      onClick: () => confirmDelete ()
    } );
  }
} );

export default DeleteButton;
</script>
