import { startStimulusApp } from '@symfony/stimulus-bundle';
import AvatarUploadController from './controllers/avatar_upload_controller.js';

const app = startStimulusApp();
app.register('avatar-upload', AvatarUploadController);
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
